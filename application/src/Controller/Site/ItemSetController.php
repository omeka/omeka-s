<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractSiteController
{
    public function showAction()
    {
        $site = $this->getSite();
        $response = $this->api()->read('item_sets', $this->params('id'));
        $itemSet = $response->getContent();
        $itemsResponse = $this->api()->search('items', [
            'item_set_id' => $itemSet->id(),
            'limit' => 5
        ]);
        $items = $itemsResponse->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('items', $items);
        $view->setVariable('resource', $itemSet);
        return $view;
    }
}
