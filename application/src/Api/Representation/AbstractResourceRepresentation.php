<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Event\Event;

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
        $jsonLd = array_merge(
            array(
                '@context' => $this->context,
                '@id' => $this->apiUrl(),
                'o:id' => $this->id(),
            ),
            $this->getJsonLd()
        );

        // Filter the JSON-LD.
        $args = array(
            'jsonLd' => $jsonLd,
            'services' => $this->getServiceLocator(),
        );
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs($args);
        $eventManager->trigger(Event::JSON_LD_FILTER, $this, $args);
        return $args['jsonLd'];
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
     * Get a reference for this resource representation.
     *
     * @return ResourceReference
     */
    public function getReference()
    {
        return new ResourceReference(
            $this->id(), $this->getData(), $this->getAdapter());
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
     * Return the URL to this resource.
     *
     * Automatically detects whether to compose an admin URL or site URL
     * depending on the current route context. To compose URLs across contexts,
     * use {@link self::adminUrl()} or {@link self::siteUrl()} directly.
     *
     * @param string $action The route action for an admin URL; does
     *   nothing for a site URL.
     * @param bool $canonical Whether to return an absolute URL
     * @return string
     */
    public function url($action = null, $canonical = false)
    {
        $routeMatch = $this->getServiceLocator()->get('Application')
            ->getMvcEvent()->getRouteMatch();
        $url = null;
        if ($routeMatch->getParam('__ADMIN__')) {
            $url = $this->adminUrl($action, $canonical);
        } elseif ($routeMatch->getParam('__SITE__')) {
            $url = $this->siteUrl($routeMatch->getParam('site-slug'), $canonical);
        }
        return $url;
    }

    /**
     * Return the admin URL to this resource.
     *
     * @param string $action The route action
     * @param bool $canonical Whether to return an absolute URL
     * @return string
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/id',
            array(
                'controller' => $this->getControllerName(),
                'action' => $action,
                'id' => $this->id(),
            ),
            array('force_canonical' => $canonical)
        );
    }

    /**
     * Return the site URL to this resource.
     *
     * Implement this method only for resources that have site URLs.
     *
     * @param string $siteSlug The site's slug
     * @param bool $canonical Whether to return an absolute URL
     * @return string
     */
    public function siteUrl($siteSlug = null, $canonical = false)
    {
        return null;
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

        $attributes['href'] = $this->url($action);
        $attributeStr = '';
        foreach ($attributes as $key => $value) {
            $attributeStr .= ' ' . $key . '="' . $escapeHtml($value) . '"';
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
