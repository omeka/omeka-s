<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Representation\SiteRepresentation;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting the current site representation.
 */
class CurrentSite extends AbstractPlugin
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
