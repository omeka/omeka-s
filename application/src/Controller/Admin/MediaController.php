<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;

class MediaController extends AbstractActionController
{
    public function searchAction()
    {
        $view = new ViewModel;
        $view->setVariable('query', $this->params()->fromQuery());
        return $view;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('media', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $medias = $response->getContent();
        $view->setVariable('medias', $medias);
        $view->setVariable('resources', $medias);
        return $view;
    }

    public function editAction()
    {
        $form = $this->getForm(ResourceForm::class);
        $response = $this->api()->read('media', $this->params('id'));
        $media = $response->getContent();

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->update('media', $this->params('id'), $data);
                if ($response) {
                    $this->messenger()->addSuccess('Media successfully updated'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('media', $media);
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('media', $this->params('id'));

        $view = new ViewModel;
        $media = $response->getContent();
        $view->setVariable('media', $media);
        $view->setVariable('resource', $media);
        return $view;
    }

    public function showDetailsAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('media', $this->params('id'));
        $media = $response->getContent();
        $values = $media->valueRepresentation();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('resource', $media);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function deleteConfirmAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('media', $this->params('id'));
        $media = $response->getContent();
        $values = $media->valueRepresentation();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $media);
        $view->setVariable('resourceLabel', 'media'); // @translate
        $view->setVariable('partialPath', 'omeka/admin/media/show-details');
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
                $response = $this->api($form)->delete('media', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Media successfully deleted'); // @translate
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

    public function sidebarSelectAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('media', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('media', $response->getContent());
        $value = $this->params()->fromQuery('value');
        $view->setVariable('searchValue', $value ? $value['in'][0] : '');
        $view->setTerminal(true);
        return $view;
    }
}
