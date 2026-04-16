<?php
namespace Omeka\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SiteController extends AbstractActionController
{
    public function resourcePickerAction()
    {
        $this->setBrowseDefaults('title');
        $query = $this->params()->fromQuery();
        $resourcePartial = $query['resource_partial'] ?? null;
        unset($query['resource_partial']);
        $response = $this->api()->search('sites', $query);
        $this->paginator($response->getTotalResults());

        $searchValue = $query['search'] ?? '';
        $slug = $query['slug'] ?? '';
        $ownerId = $query['owner_id'] ?? '';

        $view = new ViewModel;
        $view->setVariable('resources', $response->getContent());
        $view->setVariable('searchValue', $searchValue);
        $view->setVariable('slug', $slug);
        $view->setVariable('ownerId', $ownerId);
        $view->setVariable('expanded', $slug || $ownerId);
        $view->setVariable('resourcePartial', $resourcePartial);
        $view->setTerminal(true);
        return $view;
    }
}
