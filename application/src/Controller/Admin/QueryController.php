<?php
namespace Omeka\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class QueryController extends AbstractActionController
{
    public function sidebarEditAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $this->params()->fromQuery('query_resource_type'));
        return $view;
    }

    public function sidebarPreviewAction()
    {
        switch ($this->params()->fromQuery('query_resource_type')) {
            case 'item_set':
                $apiResource = 'item_sets';
                break;
            case 'media':
                $apiResource = 'media';
                break;
            default:
                $apiResource = 'items';
        }
        $this->setBrowseDefaults('created');
        $response = $this->api()->search($apiResource, $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resources', $response->getContent());
        return $view;
    }
}
