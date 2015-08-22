<?php
namespace Omeka\Controller\Site;

use Omeka\Mvc\Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->getSite();

        // Redirect to the first page, if it exists
        $pages = $site->pages();
        if ($pages) {
            $firstPage = $pages[0];
            return $this->redirect()->toRoute('site/page', array(
                'site-slug' => $site->slug(),
                'page-slug' => $firstPage->slug(),
            ));
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        return $view;
    }

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
        return $view;
    }

    public function itemAction()
    {
        $site = $this->getSite();
        $response = $this->api()->searchOne('items', array(
            'id' => $this->params('id'),
            'site_id' => $site->id(),
        ));
        if (!$response->getTotalResults()) {
            throw new Exception\NotFoundException;
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('item', $response->getContent());
        return $view;
    }

    protected function getSite()
    {
        return $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ))->getContent();
    }
}
