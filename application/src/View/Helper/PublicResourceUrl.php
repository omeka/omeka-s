<?php declare(strict_types=1);

namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceRepresentation;

/**
 * View helper to return the url to the public default site page of a resource.
 */
class PublicResourceUrl extends AbstractHelper
{
    /**
     * @var string[]
     */
    protected $siteSlugs;

    /**
     * @var ?string
     */
    protected $defaultSiteSlug;

    /**
     * @var int
     */
    protected $defaultSiteId;

    public function __construct(array $siteSlugs, ?string $defaultSiteSlug)
    {
        $this->siteSlugs = $siteSlugs;
        $this->defaultSiteSlug = $defaultSiteSlug;
        $this->defaultSiteId = (int) array_search($defaultSiteSlug, $siteSlugs);
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
    public function __invoke(AbstractResourceRepresentation $resource, $canonical = false): ?string
    {
        // Use default site by default.
        $siteSlug = $this->defaultSiteSlug;

        // The resource should belong to the site.
        $res = $resource;
        if ($resource->getResourceJsonLdType() === 'o:Media') {
            $res = $resource->item();
        }
        if (method_exists($res, 'sites')) {
            $resourceSites = $res->sites();
            if (!isset($resourceSites[$this->defaultSiteId]) && count($resourceSites)) {
                $siteSlug = (reset($resourceSites))->slug();
            }
        }

        // Manage the case where there is no site.
        return $siteSlug
            ? $resource->siteUrl($siteSlug, $canonical)
            : null;
    }
}
