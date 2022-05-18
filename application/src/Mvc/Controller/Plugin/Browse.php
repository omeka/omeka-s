<?php
namespace Omeka\Mvc\Controller\Plugin;

use Laminas\View\HelperPluginManager;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class Browse extends AbstractPlugin
{
    protected HelperPluginManager $viewHelperManager;

    public function __construct(HelperPluginManager $viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
    }

    public function setDefaults(string $resourceType) : void
    {
        $controller = $this->getController();
        $context = $controller->status()->isAdminRequest() ? 'admin' : 'public';
        $browseConfig = $this->viewHelperManager->get('browse')->getBrowseConfig($context, $resourceType);
        $query = $this->getController()->getRequest()->getQuery();
        $query->set('sort_by', $query->get('sort_by', $browseConfig[0]));
        $query->set('sort_order', $query->get('sort_order', $browseConfig[1]));
        $query->set('page', $query->get('page', $browseConfig[2]));
    }
}
