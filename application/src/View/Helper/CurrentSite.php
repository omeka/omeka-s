<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\SiteRepresentation;
use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for getting the current site representation.
 */
class CurrentSite extends AbstractHelper
{
    /**
     * @var SiteRepresentation
     */
    protected $site;

    public function setSite(SiteRepresentation $site)
    {
        $this->site = $site;
    }

    /**
     * Get the current site representation.
     *
     * @return SiteRepresentation
     */
    public function __invoke()
    {
        return $this->site;
    }
}
