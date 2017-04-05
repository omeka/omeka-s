<?php
namespace Omeka\Controller\Admin;

use Omeka\Entity\Job;
use Omeka\Form\ConfirmForm;
use Omeka\Job\Dispatcher;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class JobController extends AbstractActionController
{
    /**
     * @var Dispatcher
     */
    protected $jobDispatcher;

    /**
     * @param Dispatcher $jobDispatcher
     */
    public function __construct(Dispatcher $jobDispatcher)
    {
        $this->jobDispatcher = $jobDispatcher;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('id');
        $response = $this->api()->search('jobs', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('jobs', $response->getContent());
        return $view;
    }

    public function showAction()
    {
        $job = $this->api()->read('jobs', $this->params('id'))->getContent();
        $form = $this->getForm(ConfirmForm::class);
        $form->setAttribute('action', $job->url('stop'));
        $form->setButtonLabel('Attempt Stop');

        $view = new ViewModel;
        $view->setVariable('job', $job);
        $view->setVariable('resource', $job);
        $view->setVariable('confirmForm', $form);

        return $view;
    }

    public function logAction()
    {
        $job = $this->api()->read('jobs', $this->params('id'))->getContent();
        $response = $this->getResponse();
        $response->getHeaders()->addHeaderLine('Content-Type', 'text/plain; charset=utf-8');
        $response->setContent($job->log());
        return $response;
    }

    public function stopAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->read('jobs', $this->params('id'));
                $job = $response->getContent();
                if (Job::STATUS_IN_PROGRESS == $job->status()) {
                    $this->jobDispatcher->stop($job->id());
                    $this->messenger()->addSuccess('Attempting to stop the job.'); // @translate
                } else {
                    $this->messenger()->addError('The job could not be stopped.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
    }
}
