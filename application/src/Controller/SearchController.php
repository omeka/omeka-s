<?php
namespace Omeka\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SearchController extends AbstractActionController
{
    public function indexAction()
    {
    }

    public function itemsAdvancedAction()
    {
    }

    public function itemSetsAdvancedAction()
    {
    }

    public function resultsAction()
    {
        $query = ['fulltext_search' => $this->params()->fromQuery('fulltext_search'), 'limit' => 10];
        $view = new ViewModel;
        $view->setVariable('responseSitePages', $this->api()->search('site_pages', $query));
        $view->setVariable('responseItems', $this->api()->search('items', array_merge($query, ['in_sites' => true])));
        $view->setVariable('responseItemSets', $this->api()->search('item_sets', array_merge($query, ['in_sites' => true])));
        return $view;
    }

    public function sitePagesAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('site_pages', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());
        $view = new ViewModel;
        $view->setVariable('sitePages', $response->getContent());
        return $view;
    }

    public function itemsAction()
    {
        $this->setBrowseDefaults('created');
        $query = array_merge($this->params()->fromQuery(), ['in_sites' => true]);
        $response = $this->api()->search('items', $query);
        $this->paginator($response->getTotalResults());
        $view = new ViewModel;
        $view->setVariable('items', $response->getContent());
        return $view;
    }

    public function itemSetsAction()
    {
        $this->setBrowseDefaults('created');
        $query = array_merge($this->params()->fromQuery(), ['in_sites' => true]);
        $response = $this->api()->search('item_sets', $query);
        $this->paginator($response->getTotalResults());
        $view = new ViewModel;
        $view->setVariable('itemSets', $response->getContent());
        return $view;
    }
}
