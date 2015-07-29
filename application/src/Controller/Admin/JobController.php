<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Entity\Job;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class JobController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('id');
        $response = $this->api()->search('jobs', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

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

    public function logAction()
    {
        $response = $this->api()->read('jobs', $this->params('id'));
        $log = $response->getContent()->log();
        $escape = $this->getServiceLocator()->get('ViewHelperManager')->get('EscapeHtml');
        return $this->getResponse()->setContent('<pre>' . $escape($log) . '</pre>');
    }

    public function stopAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->read('jobs', $this->params('id'));
                $job = $response->getContent();
                if (Job::STATUS_IN_PROGRESS == $job->status()) {
                    $dispatcher = $this->getServiceLocator()->get('Omeka\JobDispatcher');
                    $dispatcher->stop($job->id());
                    $this->messenger()->addSuccess('Attempting to stop the job.');
                } else {
                    $this->messenger()->addError('The job could not be stopped.');
                }
            } else {
                $this->messenger()->addError('The job could not be stopped.');
            }
        }
        return $this->redirect()->toRoute('admin/default', array('action' => 'browse'), true);
    }
}
