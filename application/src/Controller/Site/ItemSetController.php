<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractSiteController
{
    public function showAction()
    {
        $site = $this->getSite();
        $response = $this->api()->read('item_sets', $this->params('id'));
        $item = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('itemSet', $item);
        $view->setVariable('resource', $item);
        return $view;
    }
}
