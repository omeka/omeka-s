<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Representation\SiteRepresentation;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

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

    public function __invoke()
    {
        return $this->site;
    }
}
