<?php
namespace Omeka\Controller\Site;

use Zend\View\Model\ViewModel;

class MediaController extends AbstractSiteController
{
    public function showAction()
    {
        $site = $this->getSite();
        $response = $this->api()->read('media', $this->params('id'));
        $item = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('media', $item);
        $view->setVariable('resource', $item);
        return $view;
    }
}
