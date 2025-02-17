<?php

namespace EADImport\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Stdlib\Message;
use Omeka\Form\ConfirmForm;
use EADImport\Form\MappingModelSaverForm;
use EADImport\Form\MappingModelEditForm;

class MappingModelController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('eadimport_mapping_models');
        $this->paginator($response->getTotalResults());

        $formDeleteSelected = $this->getForm(ConfirmForm::class);
        $formDeleteSelected->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'batch-delete'], true));
        $formDeleteSelected->setButtonLabel('Confirm Delete'); // @translate
        $formDeleteSelected->setAttribute('id', 'confirm-delete-selected');

        $view = new ViewModel;
        $mappingModels = $response->getContent();
        $view->setVariable('mappingModels', $mappingModels);
        $view->setVariable('formDeleteSelected', $formDeleteSelected);
        return $view;
    }

    public function showAction()
    {
        $propertiesMap = [];
        $properties = $this->api()->search('properties')->getContent();
        foreach ($properties as $property) {
            $propertiesMap[$property->id()] = $property->term();
        }
        $response = $this->api()->read('eadimport_mapping_models', $this->params('id'));

        $view = new ViewModel;
        $mappingModel = $response->getContent();
        $view->setVariable('propertiesMap', $propertiesMap);
        $view->setVariable('mappingModel', $mappingModel);
        return $view;
    }

    public function sidebarSelectAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('eadimport_mapping_models');
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('mappingModels', $response->getContent());
        $view->setTerminal(true);
        return $view;
    }

    public function editAction()
    {
        $response = $this->api()->read('eadimport_mapping_models', $this->params('id'));
        $mappingModel = $response->getContent();

        $view = new ViewModel;
        $form = $this->getForm(MappingModelEditForm::class);
        $form->setAttribute('action', $mappingModel->url('edit'));
        $form->setData([
            'model_name' => $mappingModel->name(),
        ]);

        $view->setVariable('form', $form);
        $view->setTerminal(true);
        $view->setTemplate('ead-import/admin/mapping-model/edit');
        $view->setVariable('mapping', $mappingModel);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $mappingModelName = $form->get('model_name')->getValue();

                $response = $this->api($form)->update('eadimport_mapping_models', $this->params('id'), ['name' => $mappingModelName], [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Mapping model successfully updated'); // @translate
                    return $this->redirect()->toRoute(
                        'admin/eadimport/mapping-model',
                        ['action' => 'browse'],
                        true
                    );
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $view;
    }

    public function saveAction()
    {
        $view = new ViewModel;
        $importName = $this->getRequest()->getQuery('import_name');
        $importMapping = $this->getRequest()->getQuery('import_mapping');

        $form = $this->getForm(MappingModelSaverForm::class);
        $form->setData([
            'model_name' => $importName,
            'mapping' => $importMapping,
        ]);
        $view->setVariable('form', $form);
        $view->setTerminal(true);
        $view->setTemplate('ead-import/admin/mapping-model/save');

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $mappingName = $form->get('model_name')->getValue();
                $mapping = $form->get('mapping')->getValue();

                $response = $this->api()->create('eadimport_mapping_models', ['name' => $mappingName, 'mapping' => json_decode($mapping)]);

                if ($response) {
                    $this->messenger()->addSuccess('Mapping model successfully added'); // @translate
                    return $this->redirect()->toRoute(
                        'admin/eadimport/mapping-model',
                        ['action' => 'browse'],
                        true
                    );
                } else {
                    $message = new Message(
                        'Name or mapping is empty', // @translate
                    );

                    $message->setEscapeHtml(false);

                    $this->messenger()->addError($message);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $view;
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('eadimport_mapping_models', $this->params('id'));
        $mappingModel = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $mappingModel);
        $view->setVariable('resourceLabel', 'mapping model'); // @translate
        $view->setVariable('partialPath', 'ead-import/admin/mapping-model/show-details');
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('eadimport_mapping_models', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Mapping model successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(
            'admin/eadimport/mapping-model',
            ['action' => 'browse'],
            true
        );
    }
}
