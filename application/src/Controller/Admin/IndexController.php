<?php
namespace Omeka\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function browseAction()
    {
        $sitesResponse = $this->api()->search('sites');
        $itemsResponse = $this->api()->search('items', ['limit' => 0]);
        $itemSetsResponse = $this->api()->search('item_sets', ['limit' => 0]);
        $vocabulariesResponse = $this->api()->search('vocabularies', ['limit' => 0]);
        $resourceTemplatesResponse = $this->api()->search('resource_templates', ['limit' => 0]);

        $view = new ViewModel;
        $view->setVariable('sites', $sitesResponse->getContent());
        $view->setVariable('itemCount', $itemsResponse->getTotalResults());
        $view->setVariable('itemSetCount', $itemSetsResponse->getTotalResults());
        $view->setVariable('vocabularyCount', $vocabulariesResponse->getTotalResults());
        $view->setVariable('resourceTemplateCount', $resourceTemplatesResponse->getTotalResults());
        return $view;
    }
}
