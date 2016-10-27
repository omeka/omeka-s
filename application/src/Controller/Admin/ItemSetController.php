<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceForm;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractActionController
{
    public function searchAction()
    {
        $view = new ViewModel;
        $view->setVariable('query', $this->params()->fromQuery());
        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(ResourceForm::class);
        $form->setAttribute('id', 'add-item-set');
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->create('item_sets', $data);
                if ($response->isSuccess()) {
                    $message = new Message(
                        'Item set successfully created. %s', // @translate
                        sprintf(
                            '<a href="%s">%s</a>',
                            htmlspecialchars($this->url()->fromRoute(null, [], true)),
                            $this->translate('Add another item set?')
                        ));
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $form = $this->getForm(ResourceForm::class);
        $form->setAttribute('id', 'edit-item-set');
        $response = $this->api()->read('item_sets', $this->params('id'));
        $itemSet = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('itemSet', $itemSet);
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->update('item_sets', $this->params('id'), $data);
                if ($response->isSuccess()) {
                    $this->messenger()->addSuccess('Item set successfully updated'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
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
        $view->setVariable('resource', $itemSet);
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

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $itemSet);
        $view->setVariable('resourceLabel', 'item set');
        $view->setVariable('partialPath', 'omeka/admin/item-set/show-details');
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('item_sets', $this->params('id'));
                if ($response->isSuccess()) {
                    $this->messenger()->addSuccess('Item set successfully deleted'); // @translate
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
