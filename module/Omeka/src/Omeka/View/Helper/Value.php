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
     *     - default: the default value, if the value is not found or is an
     *       empty string
     *     - all: if true, return all values
     *     - delimiter: return all values as a string, separated by the provided 
     *       delimiter
     *     - htmlescape: escape HTML characters in each value
     *     - trim: trim whitespace on both sides of each value
     *     - lang: return only those values using the provided language code
     * @return mixed
     */
    public function __invoke($resourceId, $namespaceUri, $localName,
        array $options = array()
    ) {
        // Set the options.
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
        );
        if ($options['lang']) {
            $valueData['lang'] = $options['lang'];
        }
        $response = $this->apiManager->search('values', $valueData);
        if ($response->isError()) {
            throw new Exception\RuntimeException('Error during values request.');
        }
        if (!$response->getTotalResults()) {
            return null;
        }

        // Set the callbacks.
        $callbacks = array();
        if ($options['htmlescape']) {
            $view = $this->getView();
            $callbacks[] = function ($value) use ($view) {
                return $view->escapeHtml($value);
            };
        }
        if ($options['trim']) {
            $callbacks[] = 'trim';
        }

        return $filter->get($response, 'value', array(
            'one'        => !$options['all'],
            'delimiter'  => $options['delimiter'],
            'default'    => $options['default'],
            'callbacks'  => $callbacks,
        ));
    }
}
