<?php
namespace Omeka\Api\Representation;

use Omeka\Api\ResourceInterface;
use Omeka\Api\Adapter\AdapterInterface;

/**
 * Abstract API resource representation.
 *
 * Provides functionality for representations of registered API resources.
 */
abstract class AbstractResourceRepresentation extends AbstractRepresentation
{
    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var ResourceInterface
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * Get an array representation of this resource using JSON-LD notation.
     *
     * @return array
     */
    abstract public function getJsonLd();

    /**
     * Get the linked data type or types for this resource
     *
     * @return string|array|null
     */
    abstract public function getJsonLdType();

    /**
     * Construct the resource representation object.
     *
     * @param ResourceInterface $resource
     * @param AdapterInterface $adapter
     */
    public function __construct(ResourceInterface $resource, AdapterInterface $adapter)
    {
        // Set the service locator first.
        $this->setServiceLocator($adapter->getServiceLocator());
        $this->setId($resource->getId());
        $this->setAdapter($adapter);
        $this->resource = $resource;
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
        $childJsonLd = $this->getJsonLd();
        $type = $this->getJsonLdType();
        $url = $this->getViewHelper('Url');

        $jsonLd = array_merge(
            [
                '@context' => $url('api-context', [], ['force_canonical' => true]),
                '@id' => $this->apiUrl(),
                '@type' => $type,
                'o:id' => $this->id(),
            ],
            $childJsonLd
        );

        // Filter the JSON-LD.
        $args = [
            'jsonLd' => $jsonLd,
        ];
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs($args);
        $eventManager->trigger('rep.resource.json', $this, $args);
        return $args['jsonLd'];
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
        return new ResourceReference($this->resource, $this->getAdapter());
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
            [
                'resource' => $this->getAdapter()->getResourceName(),
                'id' => $this->id(),
            ],
            ['force_canonical' => true]
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
            [
                'controller' => $this->getControllerName(),
                'action' => $action,
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
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
    public function link($text, $action = null, $attributes = [])
    {
        $escapeHtml = $this->getViewHelper('escapeHtml');
        return $this->linkRaw($escapeHtml($text), $action, $attributes);
    }

    /**
     * Get an HTML link to a resource, with the link contents unescaped.
     *
     * This method allows for more complex HTML within a link, but
     * Users of this method must ensure any untrusted components of
     * their contents are already escaped or filtered as necessary.
     *
     * Link attributes are still auto-escaped by this method.
     *
     * @param string $html The HTML to be linked
     * @param string $action
     * @param array $attributes HTML attributes, key and value
     * @return string
     */
    public function linkRaw($html, $action = null, $attributes = [])
    {
        $hyperlink = $this->getViewHelper('hyperlink');
        return $hyperlink->raw($html, $this->url($action), $attributes);
    }

    /**
     * Get a URL to a stored file.
     *
     * @param string $prefix The storage prefix
     * @param string $name The file name, or basename if extension is passed
     * @param null|string $extension The file extension
     */
    public function getFileUrl($prefix, $name, $extension = null)
    {
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        if (null !== $extension) {
            $extension = ".$extension";
        }
        $storagePath = sprintf('%s/%s%s', $prefix, $name, $extension);
        return $store->getUri($storagePath);
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

    /**
     * Get markup for embedding the JSON-LD representation of this resource in HTML.
     *
     * @return string
     */
    public function embeddedJsonLd()
    {
        echo '<script type="application/ld+json">'
            . json_encode($this)
            . '</script>';
    }
}
