<?php
namespace Omeka\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Omeka\Stdlib\Browse as BrowseService;

class Browse extends AbstractPlugin
{
    protected BrowseService $browseService;

    public function __construct(BrowseService $browseService)
    {
        $this->browseService = $browseService;
    }

    public function getBrowseService() : BrowseService
    {
        return $this->browseService;
    }

    public function setDefaults(string $resourceType) : void
    {
        $controller = $this->getController();
        $context = $controller->status()->isAdminRequest() ? 'admin' : 'public';
        $browseConfig = $this->getBrowseService()->getBrowseConfig($context, $resourceType);
        $query = $this->getController()->getRequest()->getQuery();
        $query->set('sort_by', $query->get('sort_by', $browseConfig['sort_by']));
        $query->set('sort_order', $query->get('sort_order', $browseConfig['sort_order']));
        $query->set('page', $query->get('page', 1));
    }
}
