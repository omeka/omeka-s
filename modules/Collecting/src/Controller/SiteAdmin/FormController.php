<?php
namespace Collecting\Controller\SiteAdmin;

use Collecting\Form\CollectingForm;
use Omeka\Form\ConfirmForm;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class FormController extends AbstractActionController
{
    public function indexAction()
    {
        $site = $this->currentSite();
        $this->getRequest()->getQuery()->set('site_id', $site->id());

        $this->setBrowseDefaults('id');
        $response = $this->api()->search('collecting_forms', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));
        $cForms = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('cForms', $cForms);
        return $view;
    }

    public function showAction()
    {
        $site = $this->currentSite();
        $cForm = $this->collectingCurrentForm();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('cForm', $cForm);
        return $view;
    }

    public function addAction()
    {
        return $this->handleAddEdit();
    }

    public function editAction()
    {
        return $this->handleAddEdit();
    }

    protected function handleAddEdit()
    {
        $site = $this->currentSite();
        $form = $this->getForm(CollectingForm::class);
        $isEdit = (bool) ('edit' === $this->params('action'));

        $view = new ViewModel;
        $view->setTemplate('collecting/site-admin/form/form');
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        $view->setVariable('isEdit', $isEdit);

        if ($isEdit) {
            $cForm = $this->collectingCurrentForm();
            $data = $cForm->jsonSerialize();
            $form->setData($data);
            if ($data['o:item_set']) {
                $form->get('item_set_id')->setValue($data['o:item_set']->id());
            }
            $view->setVariable('cForm', $cForm);
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $data['o:site']['o:id'] = $site->id();
            $data['o:item_set']['o:id'] = $this->params()->fromPost('item_set_id');
            $form->setData($data);
            if ($form->isValid()) {
                $response = $isEdit
                    ? $this->api($form)->update('collecting_forms', $cForm->id(), $data)
                    : $this->api($form)->create('collecting_forms', $data);
                if ($response) {
                    $cForm = $response->getContent();
                    $successMessage = $isEdit
                        ? 'Collecting form successfully updated' // @translate
                        : 'Collecting form successfully created'; // @translate
                    $this->messenger()->addSuccess($successMessage);
                    return $this->redirect()->toUrl($cForm->url('show'));
                }
            } else {
                $this->messenger()->addErrors($form->getMessages());
            }
        }

        return $view;
    }

    public function deleteConfirmAction()
    {
        $site = $this->currentSite();
        $cForm = $this->api()
            ->read('collecting_forms', $this->params('form-id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'collecting form');
        $view->setVariable('resource', $cForm);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('collecting_forms', $this->params('form-id'));
                if ($response) {
                    $this->messenger()->addSuccess('Collecting form successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addErrors($form->getMessages());
            }
        }
        return $this->redirect()->toRoute(
            'admin/site/slug/collecting/default',
            ['action' => 'index'],
            true
        );
    }
}
