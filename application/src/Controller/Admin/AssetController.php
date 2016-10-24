<?php
namespace Omeka\Controller\Admin;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class AssetController extends AbstractActionController
{
    public function sidebarSelectAction()
    {
        $this->setBrowseDefaults('id');
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
        $httpResponse->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        if ($this->getRequest()->isPost()) {
            $fileData = $this->getRequest()->getFiles()->toArray();
            $response = $this->api()->create('assets', [], $fileData);
            if ($response->isSuccess()) {
                $httpResponse->setContent(json_encode([]));
            } else {
                $errors = [];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator(
                        $response->getErrorStore()->getErrors(),
                        RecursiveArrayIterator::CHILD_ARRAYS_ONLY
                    )
                );
                foreach ($iterator as $error) {
                    $errors[] = $this->translate($error);
                }
                $httpResponse->setContent(json_encode($errors));
                $httpResponse->setStatusCode(422);
            }
        } else {
            $httpResponse->setContent(json_encode([$this->translate('Asset uploads must be POSTed.')]));
            $httpResponse->setStatusCode(405);
        }

        return $httpResponse;
    }
}
