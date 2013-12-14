<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\ResponseFilter;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Exception;

/**
 * Helper for getting values from a resource.
 */
class Value extends AbstractHelper
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceManager
     */
    public function __construct(ServiceLocatorInterface $serviceManager)
    {
        $this->apiManager = $serviceManager->get('ApiManager');
    }

    /**
     * Return the requested value or values.
     *
     * @param int $resourceId
     * @param string $namespaceUri The namespace URI of the vocabulary
     * @param string $localName The local name of the property
     * @param array $options
     *   - type: the type of value (default: "literal")
     *   - default: the default value, if the value is not found or is an empty
     *     string (default: null)
     *   - all: if true, return all values (default: false)
     *   - delimiter: return all values as a string, separated by the given 
     *     delimiter (default: false)
     *   - htmlescape: escape HTML characters in each value (default: true)
     *   - trim: trim whitespace on both sides of each value (default: false)
     *   - lang: only return values tagged with the given language code
     *     (default: false)
     *   - truncate: truncate each value to the given character length (default:
     *     false)
     * @return mixed
     */
    public function __invoke($resourceId, $namespaceUri, $localName,
        array $options = array()
    ) {
        // Set the options.
        if (!isset($options['type'])) {
            $options['type'] = 'literal';
        }
        if (!isset($options['default'])) {
            $options['default'] = null;
        }
        if (!isset($options['all'])) {
            $options['all'] = false;
        }
        if (!isset($options['delimiter'])) {
            $options['delimiter'] = false;
        }
        if (!isset($options['htmlescape'])) {
            $options['htmlescape'] = true;
        }
        if (!isset($options['trim'])) {
            $options['trim'] = true;
        }
        if (!isset($options['lang'])) {
            $options['lang'] = false;
        }
        if (!isset($options['truncate'])) {
            $options['truncate'] = false;
        }

        $filter = new ResponseFilter;

        // Get the specified property.
        $response = $this->apiManager->search('properties', array(
            'vocabulary' => array('namespace_uri' => $namespaceUri),
            'local_name' => $localName,
        ));
        if ($response->isError()) {
            throw new Exception\RuntimeException('Error during properties request.');
        }
        if (!$response->getTotalResults()) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Property not found using "%s" and "%s".',
                $namespaceUri, $localName
            ));
        }
        $valueData = array(
            'resource' => array('id' => $resourceId),
            'property' => array('id' => $filter->get($response, 'id', array('one' => true))),
            'type'     => $options['type'],
        );
        if ($options['lang']) {
            $valueData['lang'] = $options['lang'];
        }
        $response = $this->apiManager->search('values', $valueData);
        if ($response->isError()) {
            throw new Exception\RuntimeException('Error during values request.');
        }
        if (!$response->getTotalResults()) {
            return $options['default'];
        }

        // Only literal values can be formatted as strings.
        if ('literal' !== $options['type']) {
            return $filter->get($response, 'value', array(
                'one'     => !$options['all'],
                'default' => $options['default'],
            ));
        }

        // Set the callbacks in order of desired execution.
        $callbacks = array();
        $view = $this->getView();
        if ($options['trim']) {
            $callbacks[] = 'trim';
        }
        if ($options['truncate']) {
            $truncate = (int) $options['truncate'];
            $callbacks[] = function ($value) use ($view, $truncate) {
                // @todo further develop the truncate function and port  it to a
                // view helper. See http://stackoverflow.com/questions/79960/how-to-truncate-a-string-in-php-to-the-word-closest-to-a-certain-number-of-chara
                return substr($value, 0, $truncate);
            };
        }
        if ($options['htmlescape']) {
            $callbacks[] = function ($value) use ($view) {
                return $view->escapeHtml($value);
            };
        }

        return $filter->get($response, 'value', array(
            'one'        => !$options['all'],
            'delimiter'  => $options['delimiter'],
            'default'    => $options['default'],
            'callbacks'  => $callbacks,
        ));
    }
}
