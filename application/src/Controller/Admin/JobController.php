<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class JobController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }

    public function browseAction()
    {
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array(
            'page' => $page,
            'sort_by' => $this->params()->fromQuery('sort_by', 'label'),
        );
        $response = $this->api()->search('jobs', $query);
        $this->paginator($response->getTotalResults(), $page);

        $view = new ViewModel;
        $view->setVariable('jobs', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Attempt Stop'),
            )
        ));
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('jobs', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('job', $response->getContent());
        return $view;
    }

    public function stopAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                $dispatcher->stop($this->params('id'));
                $this->messenger()->addSuccess('Attempting to stop the job.');
            } else {
                $this->messenger()->addError('The job could not be stopped.');
            }
        }
        return $this->redirect()->toRoute('admin/default', array('action' => 'browse'), true);
    }
}
