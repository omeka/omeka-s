<?php
namespace Omeka\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class QueryController extends AbstractActionController
{
    public function sidebarEditAction()
    {
        switch ($this->params()->fromQuery('query_resource_type')) {
            case 'media':
                $resourceType = 'media';
                break;
            case 'item_sets':
                $resourceType = 'itemSet';
                break;
            default:
                $resourceType = 'item';
        }
        $partialExcludelist = json_decode($this->params()->fromQuery('query_partial_excludelist'), true);

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('partialExcludelist', $partialExcludelist);
        return $view;
    }

    public function sidebarPreviewAction()
    {
        switch ($this->params()->fromQuery('query_resource_type')) {
            case 'media':
                $resourceType = 'media';
                break;
            case 'item_sets':
                $resourceType = 'item_sets';
                break;
            default:
                $resourceType = 'items';
        }
        $this->setBrowseDefaults('created');

        $previewAppendQuery = json_decode($this->params()->fromQuery('query_preview_append_query'), true);
        $query = array_merge($this->params()->fromQuery(), $previewAppendQuery);

        $response = $this->api()->search($resourceType, $query);
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resources', $response->getContent());
        return $view;
    }

    public function searchFiltersAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('queryArray', $this->params()->fromQuery());
        return $view;
    }
}
