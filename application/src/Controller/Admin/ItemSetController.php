<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'item-set',
            'action' => 'browse',
        ));
    }

    public function browseAction()
    {
        $view = new ViewModel;

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('item_sets', $query);

        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('itemSets', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    public function showAction()
    {}

    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
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
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'item-set',
            'action'     => 'browse',
        ));
    }
}
