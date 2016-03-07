<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ItemForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;

class ItemController extends AbstractActionController
{
    public function searchAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('items', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $items = $response->getContent();
        $view->setVariable('items', $items);
        $view->setVariable('resources', $items);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('items', $this->params('id'));

        $view = new ViewModel;
        $item = $response->getContent();
        $view->setVariable('item', $item);
        $view->setVariable('resource', $item);
        return $view;
    }

    public function showDetailsAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('items', $this->params('id'));
        $item = $response->getContent();
        $values = $item->valueRepresentation();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('item', $item);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function sidebarSelectAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('items', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('items', $response->getContent());
        $value = $this->params()->fromQuery('value');
        $view->setVariable('searchValue', $value ? $value['in'][0] : '');
        $view->setVariable('showDetails', true);
        $view->setTerminal(true);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('items', $this->params('id'));
        $item = $response->getContent();
        $values = $item->valueRepresentation();
        $confirmForm = new ConfirmForm($this->getServiceLocator());
        $confirmForm->setAttribute('action',$item->url('delete'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('type', 'item');
        $view->setVariable('recordLabel', 'item');
        $view->setVariable('confirmForm', $confirmForm);
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('item', $item);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('items', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Item could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Item successfully deleted');
                }
            } else {
                $this->messenger()->addError('Item could not be deleted');
            }
        }
        return $this->redirect()->toRoute(
            'admin/default',
            ['action' => 'browse'],
            true
        );
    }

    public function addAction()
    {
        $form = new ItemForm($this->getServiceLocator());
        $form->setAttribute('id', 'add-item');
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $fileData = $this->getRequest()->getFiles()->toArray();
                $response = $this->api()->create('items', $data, $fileData);
                if ($response->isError()) {
                    $errors = $response->getErrors();
                    $form->setMessages($errors);
                    $this->messenger()->addErrors($errors);
                } else {
                    $this->messenger()->addSuccess('Item Created.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('mediaForms', $this->getMediaForms());
        return $view;
    }

    public function editAction()
    {
        $form = new ItemForm($this->getServiceLocator());
        $form->setAttribute('id', 'edit-item');
        $id = $this->params('id');
        $response = $this->api()->read('items', $id);
        $item = $response->getContent();

        $form->get('o:item_set')->setValue(array_keys($item->itemSets()));

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('item', $item);
        $view->setVariable('mediaForms', $this->getMediaForms());

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $fileData = $this->getRequest()->getFiles()->toArray();
                $response = $this->api()->update('items', $id, $data, $fileData);
                if ($response->isError()) {
                    $errors = $response->getErrors();
                    $form->setMessages($errors);
                    $this->messenger()->addErrors($errors);
                } else {
                    $this->messenger()->addSuccess('Item Updated.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        return $view;
    }

    protected function getMediaForms()
    {
        $services = $this->getServiceLocator();
        $mediaHelper = $services->get('ViewHelperManager')->get('media');
        $mediaIngester = $services->get('Omeka\MediaIngesterManager');

        $forms = [];
        foreach ($mediaIngester->getRegisteredNames() as $ingester) {
            $forms[$ingester] = [
                'label' => $mediaIngester->get($ingester)->getLabel(),
                'form' => $mediaHelper->form($ingester)
            ];
        }
        return $forms;
    }
}
