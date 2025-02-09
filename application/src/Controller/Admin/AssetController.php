<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\AssetEditForm;
use Omeka\Form\ConfirmForm;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class AssetController extends AbstractActionController
{
    public function browseAction()
    {
        $this->browse()->setDefaults('assets');
        $response = $this->api()->search('assets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $items = $response->getContent();
        $view->setVariable('assets', $items);
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('assets', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function sidebarSelectAction()
    {
        $this->setBrowseDefaults('id');
        $response = $this->api()->search('assets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

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
            $postData = $this->params()->fromPost();
            $fileData = $this->getRequest()->getFiles()->toArray();
            try {
                $response = $this->api(null, true)->create('assets', $postData, $fileData);
                $httpResponse->setContent(json_encode([]));
            } catch (ValidationException $e) {
                $errors = [];
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveArrayIterator(
                        $e->getErrorStore()->getErrors(),
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

    public function editAction()
    {
        $form = $this->getForm(AssetEditForm::class);
        $form->setAttribute('action', $this->url()->fromRoute(null, [], true));
        $form->setAttribute('id', 'edit-item');
        $asset = $this->api()->read('assets', $this->params('id'))->getContent();

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->update('assets', $this->params('id'), $data);
                if ($response) {
                    $this->messenger()->addSuccess('Asset successfully updated'); // @translate
                    return $this->redirect()->toRoute(
                        'admin/default',
                        ['action' => 'browse'],
                        true
                    );
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            $form->setData([
                'o:name' => $asset->name(),
                'o:alt_text' => $asset->altText(),
            ]);
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('asset', $asset);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $resource = $this->api()->read('assets', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $resource);
        $view->setVariable('resourceLabel', 'asset'); // @translate
        $view->setVariable('partialPath', 'omeka/admin/asset/show-details');
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('assets', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Asset successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(
            'admin/default',
            ['action' => 'browse'],
            true
        );
    }
}
