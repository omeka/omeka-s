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

    public function itemsAdvancedAction()
    {
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        return $view;
    }

    public function itemSetsAdvancedAction()
    {
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        return $view;
    }

    public function resultsAction()
    {
        $query = [
            'limit' => 10,
            'fulltext_search' => $this->params()->fromQuery('fulltext_search'),
        ];
        $resources = [
            'site_pages' => [
                'action' => 'site-pages',
                'query' => $query,
                'response' => null,
            ],
            'items' => [
                'action' => 'items',
                'query' => array_merge($query, ['in_sites' => true]),
                'response' => null,
            ],
            'item_sets' => [
                'action' => 'item-sets',
                'query' => array_merge($query, ['in_sites' => true]),
                'response' => null,
            ],
        ];
        $resourceNames = $this->siteSettings()->get('search_resource_names', ['site_pages', 'items']);
        if (1 === count($resourceNames)) {
            $resourceName = reset($resourceNames);
            return $this->redirect()->toRoute(
                'site/cross-site-search',
                ['action' => $resources[$resourceName]['action']],
                ['query' => $resources[$resourceName]['query']],
                true
            );
        }
        foreach ($resourceNames as $resourceName) {
            $resources[$resourceName]['response'] = $this->api()->search($resourceName, $resources[$resourceName]['query']);
        }

        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        $view->setVariable('responseSitePages', $resources['site_pages']['response']);
        $view->setVariable('responseItems', $resources['items']['response']);
        $view->setVariable('responseItemSets', $resources['item_sets']['response']);
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

    public function itemSetsAction()
    {
        $this->setBrowseDefaults('created');
        $query = array_merge($this->params()->fromQuery(), ['in_sites' => true]);
        $response = $this->api()->search('item_sets', $query);
        $this->paginator($response->getTotalResults());
        $view = new ViewModel;
        $view->setVariable('site', $this->currentSite());
        $view->setVariable('itemSets', $response->getContent());
        return $view;
    }
}
