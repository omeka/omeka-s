<?php
namespace Omeka\Api;

/**
 * Filter response resources by key.
 */
class ResponseFilter
{
    /**
     * Get one or more values from a response, by key.
     *
     * @param Response $response
     * @param string|array $key The key of the value, or, if an array, the keys
     * to the value
     * @param array $options
     *   - default: (default: null) the default value, if the value is not found
     *     or matches one of the default_if values
     *   - default_if: (default: array("")) an array of values to match against
     *     the returned value; return the default value if the two values are
     *     identical
     *   - one: (default: false) if true, return only one value (used only for
     *     "search" and "batch create" operations)
     *   - delimiter: (default: false) return all values as a string, separated
     *     by the given delimiter 
     *   - callbacks: (default: array()) an array of callbacks to apply to each
     *     value, called in array order
     * @return mixed
     */
    public function get(Response $response, $key, array $options = array()) {

        // Set the options.
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        if (!isset($options['default_if'])) {
            $options['default_if'] = array('');
        }
        if (!isset($options['one'])) {
            $options['one'] = false;
        }
        if (!isset($options['delimiter'])) {
            $options['delimiter'] = false;
        }
        if (!isset($options['callbacks'])) {
            $options['callbacks'] = array();
        }

        // Do not filter responses that are errors or have no resources.
        if ($response->isError() || !is_array($response->getContent())) {
            return $options['default'];
        }

        switch ($response->getRequest()->getOperation()) {
            // The "search" and "batch create" operations return an array of
            // resources.
            case Request::SEARCH:
            case Request::BATCH_CREATE:
                $resources = $response->getContent();
                if ($options['one']) {
                    return isset($resources[0])
                        ? $this->getValue($resources[0], $key, $options)
                        : $options['default'];
                }
                $values = array();
                foreach ($resources as $resource) {
                    $values[] = $this->getValue($resource, $key, $options);
                }
                return false !== $options['delimiter']
                    ? implode($options['delimiter'], $values) : $values;
            // All other operations return one resource.
            default:
                return $this->getValue($response->getContent(), $key, $options);
        }
    }

    /**
     * Get a value from a resource.
     *
     * @param array $resource
     * @param string|array $key
     * @param array $options
     * @return mixed
     */
    protected function getValue(array $resource, $key, array $options)
    {
        $keys = is_array($key) ? $key: array($key);
        // Check that the value exists, iteratively if an array.
        $value = $resource;
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $options['default'];
            }
        }
        // Check for default value.
        foreach ($options['default_if'] as $defaultIf) {
            if ($value === $defaultIf) {
                return $options['default'];
            }
        }
        // Apply all callbacks to the value before returning it.
        return array_reduce($options['callbacks'], function ($value, $callback) {
            return $callback($value);
        }, $value);
    }
}
