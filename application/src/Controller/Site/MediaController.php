<?php
namespace Omeka\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class MediaController extends AbstractActionController
{
    public function showAction()
    {
        $site = $this->currentSite();
        $response = $this->api()->read('media', $this->params('id'));
        $item = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('media', $item);
        $view->setVariable('resource', $item);
        return $view;
    }
}
