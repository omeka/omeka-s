<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractActionController
{
    public function searchAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function addAction()
    {
        $form = new ResourceForm($this->getServiceLocator());
        $form->setAttribute('id', 'add-item-set');
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $response = $this->api()->create('item_sets', $data);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Item Set Created.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $form = new ResourceForm($this->getServiceLocator());
        $form->setAttribute('id', 'edit-item-set');
        $id = $this->params('id');
        $response = $this->api()->read('item_sets', $id);
        $itemSet = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $response = $this->api()->update('item_sets', $id, $data);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Item Set Updated.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        return $view;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('item_sets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $itemSets = $response->getContent();
        $view->setVariable('itemSets', $itemSets);
        $view->setVariable('resources', $itemSets);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('item_sets', $this->params('id'));

        $view = new ViewModel;
        $itemSet = $response->getContent();
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('resource', $itemSet);
        return $view;
    }

    public function showDetailsAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('item_sets', $this->params('id'));
        $itemSet = $response->getContent();
        $values = $itemSet->valueRepresentation();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function sidebarSelectAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('item_sets', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('itemSets', $response->getContent());
        $value = $this->params()->fromQuery('value');
        $view->setVariable('searchValue', $value ? $value['in'][0] : '');
        $view->setTerminal(true);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('item_sets', $this->params('id'));
        $itemSet = $response->getContent();
        $values = $itemSet->valueRepresentation();
        $confirmForm = new ConfirmForm($this->getServiceLocator());
        $confirmForm->setAttribute('action',$itemSet->url('delete'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('partialPath', 'omeka/admin/item-set/show-details');
        $view->setVariable('recordLabel', 'item set');
        $view->setVariable('confirmForm', $confirmForm);
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('item_sets', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Item set could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Item set successfully deleted');
                }
            } else {
                $this->messenger()->addError('Item set could not be deleted');
            }
        }
        return $this->redirect()->toRoute(
            'admin/default',
            ['action' => 'browse'],
            true
        );
    }
}
