<?php
namespace Omeka\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function browseAction()
    {
        $sitesResponse = $this->api()->search('sites', $this->params()->fromQuery());
        $itemsResponse = $this->api()->search('items', $this->params()->fromQuery());
        $itemSetsResponse = $this->api()->search('item_sets', $this->params()->fromQuery());
        $vocabulariesResponse = $this->api()->search('vocabularies', $this->params()->fromQuery());
        $resourceTemplatesResponse = $this->api()->search('resource_templates', $this->params()->fromQuery());

        $view = new ViewModel;
        $view->setVariable('sites', $sitesResponse->getContent());
        $view->setVariable('itemCount', count($itemsResponse->getContent()));
        $view->setVariable('itemSetCount', count($itemSetsResponse->getContent()));
        $view->setVariable('vocabularyCount', count($vocabulariesResponse->getContent()));
        $view->setVariable('resourceTemplateCount', count($resourceTemplatesResponse->getContent()));
        return $view;
    }
}
