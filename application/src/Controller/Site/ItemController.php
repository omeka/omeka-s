<?php
namespace Omeka\Controller\Site;

use Omeka\Mvc\Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemController extends AbstractActionController
{
    public function browseAction()
    {
        $site = $this->getSite();

        $this->setBrowseDefaults('created');
        $this->getRequest()->getQuery()->set('site_id', $site->id());
        $response = $this->api()->search('items', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $items = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('items', $items);
        $view->setVariable('resources', $items);
        return $view;
    }

    public function showAction()
    {
        $site = $this->getSite();
        $response = $this->api()->searchOne('items', array(
            'id' => $this->params('id'),
            'site_id' => $site->id(),
        ));
        if (!$response->getTotalResults()) {
            throw new Exception\NotFoundException;
        }
        $item = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('item', $item);
        $view->setVariable('resource', $item);
        return $view;
    }

    protected function getSite()
    {
        return $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ))->getContent();
    }
}
