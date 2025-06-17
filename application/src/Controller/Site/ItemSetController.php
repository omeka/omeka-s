<?php
namespace Omeka\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ItemSetController extends AbstractActionController
{
    public function searchAction()
    {
    }

    public function browseAction()
    {
        $site = $this->currentSite();

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();

        //this is the same approach as admin, but it will change the sort order and other defaults
//        $this->browse()->setDefaults('item_sets');

        //this will only set the page
        $query['page'] = $query['page'] ?? 1;

        $response = $this->api()->search('item_sets', $query);
        $this->paginator($response->getTotalResults());
        $itemSets = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('itemSets', $itemSets);
        $view->setVariable('resources', $itemSets);
        return $view;
    }
}
