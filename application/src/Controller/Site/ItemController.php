<?php
namespace Omeka\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemController extends AbstractActionController
{
    public function searchAction()
    {
    }

    public function browseAction()
    {
        $site = $this->currentSite();

        $this->setBrowseDefaults('created');

        $view = new ViewModel;

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();
        if ($this->siteSettings()->get('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }
        if ($itemSetId = $this->params('item-set-id')) {
            $itemSetResponse = $this->api()->read('item_sets', $this->params('item-set-id'));
            $itemSet = $itemSetResponse->getContent();
            $view->setVariable('itemSet', $itemSet);
            $query['item_set_id'] = $this->params('item-set-id');
        }

        $response = $this->api()->search('items', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $items = $response->getContent();

        $view->setVariable('site', $site);
        $view->setVariable('items', $items);
        $view->setVariable('resources', $items);
        return $view;
    }

    public function showAction()
    {
        $site = $this->currentSite();
        $response = $this->api()->read('items', $this->params('id'));
        $item = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('item', $item);
        $view->setVariable('resource', $item);
        return $view;
    }
}
