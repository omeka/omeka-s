<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\AbstractResourceRepresentation;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper to return the url to the public default site page of a resource.
 */
class PublicResourceUrl extends AbstractHelper
{
    /**
     * @var string
     */
    protected $defaultSiteSlug;

    /**
     * Construct the helper.
     *
     * @param string|null $defaultSiteSlug
     */
    public function __construct($defaultSiteSlug)
    {
        $this->defaultSiteSlug = $defaultSiteSlug;
    }

    /**
     * Return the url to the public default site page or a resource.
     *
     * @uses AbstractResourceRepresentation::siteUrl()
     *
     * @param AbstractResourceRepresentation $resource
     * @param bool $canonical Whether to return an absolute URL
     * @return string
     */
    public function __invoke(AbstractResourceRepresentation $resource, $canonical = false)
    {
        // Manage the case where there is no site.
        return $this->defaultSiteSlug
            ? $resource->siteUrl($this->defaultSiteSlug, $canonical)
            : '';
    }
}
