<?php
namespace Omeka\Api;

/**
 * Filter response resources by key.
 */
class ResponseFilter
{
    /**
     * @var array Default options
     */
    protected $options = array(
        'default'    => null,
        'default_if' => array(''),
        'one'        => false,
        'delimiter'  => false,
        'callbacks'  => array(),
    );

    /**
     * Get one or more values from a response, by key.
     *
     * @param Response $response
     * @param string|array $key The key of the value, or, if an array, the keys
     * to the value
     * @param array $options
     *   - default: the default value, if the value is not found or matches one
     *     of the default_if values.
     *   - default_if: an array of values to match against the returned value;
     *     return the default value if the two values are identical.
     *   - one: if true, return only one value (used only for "search" and
     *     "batch create" operations)
     *   - delimiter: return all values as a string, separated by the provided 
     *     delimiter
     *   - callbacks: an array of callbacks to apply to each value, called in
     *     array order
     * @return mixed
     */
    public function get(Response $response, $key, array $options = array()) {

        // Set the options.
        if (isset($options['default'])) {
            $this->options['default'] = $options['default'];
        }
        if (isset($options['default_if']) && is_array($options['default_if'])) {
            $this->options['default_if'] = $options['default_if'];
        }
        if (isset($options['one'])) {
            $this->options['one'] = (bool) $options['one'];
        }
        if (isset($options['delimiter']) && false !== $options['delimiter']) {
            $this->options['delimiter'] = (string) $options['delimiter'];
        }
        if (isset($options['callbacks']) && is_array($options['callbacks'])) {
            $this->options['callbacks'] = $options['callbacks'];
        }

        // Do not filter responses that are errors or have no resources.
        if ($response->isError() || !is_array($response->getContent())) {
            return $this->options['default'];
        }

        switch ($response->getRequest()->getOperation()) {
            // The "search" and "batch create" operations return an array of
            // resources.
            case Request::SEARCH:
            case Request::BATCH_CREATE:
                $resources = $response->getContent();
                if ($this->options['one']) {
                    return isset($resources[0])
                        ? $this->getValue($resources[0], $key)
                        : $this->options['default'];
                }
                $values = array();
                foreach ($resources as $resource) {
                    $values[] = $this->getValue($resource, $key);
                }
                return false !== $this->options['delimiter']
                    ? implode($this->options['delimiter'], $values) : $values;
            // All other operations return one resource.
            default:
                return $this->getValue($response->getContent(), $key);
        }
    }

    /**
     * Get a value from a resource.
     *
     * @param array $resource
     * @param string|array $key The key of the value, or, if an array, the keys
     * to the value
     * @return mixed
     */
    protected function getValue(array $resource, $key)
    {
        $keys = is_array($key) ? $key: array($key);
        // Check that the value exists.
        $value = $resource;
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $this->options['default'];
            }
        }
        // Check for default value.
        foreach ($this->options['default_if'] as $defaultIf) {
            if ($value === $defaultIf) {
                return $this->options['default'];
            }
        }
        // Apply all callbacks to the value before returning it.
        return array_reduce($this->options['callbacks'], function ($value, $callback) {
            return $callback($value);
        }, $value);
    }
}
