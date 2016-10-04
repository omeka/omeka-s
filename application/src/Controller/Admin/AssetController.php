<?php
namespace Omeka\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class AssetController extends AbstractActionController
{
    public function sidebarSelectAction()
    {
        $response = $this->api()->search('assets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('assets', $response->getContent());
        $view->setTerminal(true);
        return $view;
    }

    public function addAction()
    {
        $httpResponse = $this->getResponse();
        $httpResponse->getHeaders()->addHeaderLine('Content-Type', 'text/plain');
        if ($this->getRequest()->isPost()) {
            $fileData = $this->getRequest()->getFiles()->toArray();
            $response = $this->api()->create('assets', [], $fileData);
            if ($response->isSuccess()) {
                $httpResponse->setContent('Success!');
            } else {
                $httpResponse->setContent(json_encode($response->getErrorStore()->getErrors()));
                $httpResponse->setStatusCode(500);
            }
        } else {
            $httpResponse->setContent('Asset uploads must be POSTed.');
            $httpResponse->setStatusCode(405);
        }

        return $httpResponse;
    }
}
