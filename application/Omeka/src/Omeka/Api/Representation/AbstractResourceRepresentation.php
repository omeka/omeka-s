<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;

/**
 * Abstract API resource representation.
 *
 * Provides functionality for representations of registered API resources.
 */
abstract class AbstractResourceRepresentation extends AbstractRepresentation
{
    /**
     * The vocabulary IRI used to define Omeka application data.
     */
    const OMEKA_VOCABULARY_IRI = 'http://omeka.org/s/vocabulary#';

    /**
     * The JSON-LD term that expands to the vocabulary IRI.
     */
    const OMEKA_VOCABULARY_TERM = 'o';

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var array The JSON-LD context.
     */
    protected $context = array(
        self::OMEKA_VOCABULARY_TERM => self::OMEKA_VOCABULARY_IRI,
    );

    /**
     * Get an array representation of this resource using JSON-LD notation.
     *
     * @return array
     */
    abstract public function getJsonLd();

    /**
     * Construct the resource representation object.
     *
     * @param string|int $id The unique identifier of this resource
     * @param mixed $data The data from which to derive a representation
     * @param ServiceLocatorInterface $adapter The corresponsing adapter
     */
    public function __construct($id, $data, AdapterInterface $adapter)
    {
        // Set the service locator first.
        $this->setServiceLocator($adapter->getServiceLocator());
        $this->setId($id);
        $this->setData($data);
        $this->setAdapter($adapter);
    }

    /**
     * Get the unique resource identifier.
     *
     * @return string|int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Compose the complete JSON-LD object.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $jsonLd = $this->getJsonLd();
        return array_merge(
            array(
                '@context' => $this->context,
                '@id' => $this->apiUrl(),
                'o:id' => $this->id(),
            ),
            $jsonLd
        );
    }

    /**
     * Add a term definition to the JSON-LD context.
     *
     * @param string $term
     * @param string|array $map The IRI or an array defining the term
     */
    protected function addTermDefinitionToContext($term, $map)
    {
        $this->context[$term] = $map;
    }

    /**
     * Set the unique resource identifier.
     *
     * @param $id
     */
    protected function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set the corresponding adapter.
     *
     * @param AdapterInterface $adapter
     */
    protected function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the corresponding adapter or another adapter by resource name.
     *
     * @param null|string $resourceName
     * @return AdapterInterface
     */
    protected function getAdapter($resourceName = null)
    {
        if (is_string($resourceName)) {
            return parent::getAdapter($resourceName);
        }
        return $this->adapter;
    }

    /**
     * Get the URL to the represented resource in the API
     *
     * @return string
     */
    public function apiUrl()
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'api/default',
            array(
                'resource' => $this->getAdapter()->getResourceName(),
                'id' => $this->id()
            ),
            array('force_canonical' => true)
        );
    }

    /**
     * Get a web URL to the represented resource
     *
     * @uses self::getControllerName()
     * @param string $action
     * @return string|null
     */
    public function url($action = null)
    {
        if (!($controller = $this->getControllerName())) {
            return null;
        }

        $url = $this->getViewHelper('Url');
        return $url(
            'admin/id',
            array(
                'controller' => $controller,
                'action' => $action,
                'id' => $this->id(),
            )
        );
    }

    /**
     * Get an HTML link to a resource.
     *
     * @param string $text The text to be linked
     * @param string $action
     * @param array $attributes HTML attributes, key and value
     * @return string
     */
    public function link($text, $action = null, $attributes = array())
    {
        $escapeHtml = $this->getViewHelper('escapeHtml');
        $escapeHtmlAttr = $this->getViewHelper('escapeHtmlAttr');

        $attributes['href'] = $this->url($action);
        $attributeStr = '';
        foreach ($attributes as $key => $value) {
            $attributeStr .= ' ' . $key . '="' . $escapeHtmlAttr($value) . '"';
        }
        return "<a$attributeStr>" . $escapeHtml($text) . '</a>';
    }

    /**
     * Get the name for the controller that handles this kind of resource.
     *
     * @return string|null
     */
    protected function getControllerName()
    {
        return null;
    }
}
