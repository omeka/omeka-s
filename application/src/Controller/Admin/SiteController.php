<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SiteController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }

    public function browseAction()
    {
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('sites', $query);
        $this->paginator($response->getTotalResults(), $page);

        $view = new ViewModel;
        $view->setVariable('sites', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('sites', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('site', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('sites', $this->params('id'));
        $site = $response->getContent();
        $view = new ViewModel;
        $view->setTerminal(true);

        $view->setVariable('site', $site);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('sites', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Site could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Site successfully deleted');
                }
            } else {
                $this->messenger()->addError('Site could not be deleted');
            }
        }
        return $this->redirect()->toRoute(
            'admin/default',
            array('action' => 'browse'),
            true
        );
    }
}
