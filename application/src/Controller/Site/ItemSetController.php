<?php
namespace Omeka\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractActionController
{
    public function searchAction()
    {
    }

    public function browseAction()
    {
        $site = $this->currentSite();

        $this->setBrowseDefaults('created');

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();
        $response = $this->api()->search('item_sets', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $itemSets = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('itemSets', $itemSets);
        $view->setVariable('resources', $itemSets);
        return $view;
    }
}
