<?php
namespace Omeka\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CrossSiteSearchController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        return $view;
    }

    public function advancedAction()
    {
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        return $view;
    }

    public function resultsAction()
    {
        $fulltextSearch = $this->params()->fromQuery('fulltext_search');
        $resourceNames = $this->siteSettings()->get('search_resource_names', ['site_pages', 'items']);
        if (1 === count($resourceNames)) {
            return $this->redirect()->toRoute(
                null,
                ['action' => ('site_pages' === reset($resourceNames)) ? 'site-pages' : 'items'],
                ['query' => ['fulltext_search' => $fulltextSearch]],
                true
            );
        }
        $query = ['fulltext_search' => $fulltextSearch, 'limit' => 10];
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        $view->setVariable('responseSitePages', $this->api()->search('site_pages', $query));
        $view->setVariable('responseItems', $this->api()->search('items', array_merge($query, ['in_sites' => true])));
        return $view;
    }

    public function sitePagesAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('site_pages', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
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
        $view->setVariable('site', $this->currentSite());
        $view->setVariable('items', $response->getContent());
        return $view;
    }
}
