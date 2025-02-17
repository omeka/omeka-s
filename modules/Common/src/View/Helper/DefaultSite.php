<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SiteRepresentation;

/**
 * Get the configured default site, or the first public site, or the first site.
 */
class DefaultSite extends AbstractHelper
{
    /**
     * @var ?\Omeka\Api\Representation\SiteRepresentation
     */
    protected $defaultSite = null;

    /**
     * @var ?int
     */
    protected $defaultSiteId = null;

    /**
     * @var ?string
     */
    protected $defaultSiteSlug = null;

    public function __construct(?SiteRepresentation $site)
    {
        $this->defaultSite = $site;
        if ($site) {
            $this->defaultSiteId = $site->id();
            $this->defaultSiteSlug = $site->slug();
        }
    }

    public function __invoke(?string $metadata = null)
    {
        if ($metadata === 'slug') {
            return $this->defaultSiteSlug;
        } elseif ($metadata === 'id') {
            return $this->defaultSiteId;
        } elseif ($metadata === 'id_slug') {
            return $this->defaultSiteId
            ? [$this->defaultSiteId => $this->defaultSiteSlug]
            : [];
        } elseif ($metadata === 'slug_id') {
            return $this->defaultSiteId
            ? [$this->defaultSiteSlug => $this->defaultSiteId]
            : [];
        } else {
            return $this->defaultSite;
        }
    }
}
