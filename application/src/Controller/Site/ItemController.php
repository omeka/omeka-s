<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class ItemController extends AbstractSiteController
{
    public function browseAction()
    {
        $site = $this->getSite();

        $this->setBrowseDefaults('created');
        $itemPool = is_array($site->itemPool()) ? $site->itemPool() : [];
        $settings = $this->getServiceLocator()->get('Omeka\SiteSettings');
        if ($settings->get('browse_attached_items', false)) {
            $itemPool['site_id'] = $site->id();
        }

        $view = new ViewModel;

        $query = array_merge($itemPool, $this->params()->fromQuery());
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
        $site = $this->getSite();
        $response = $this->api()->read('items', $this->params('id'));
        $item = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('item', $item);
        $view->setVariable('resource', $item);
        return $view;
    }
}
