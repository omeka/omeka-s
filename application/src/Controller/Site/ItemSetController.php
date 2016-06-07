<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractSiteController
{
    public function browseAction()
    {
        $site = $this->getSite();

        $this->setBrowseDefaults('created');

        $response = $this->api()->search('item_sets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $itemSets = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('itemSets', $itemSets);
        $view->setVariable('resources', $itemSets);
        return $view;
    }
}
