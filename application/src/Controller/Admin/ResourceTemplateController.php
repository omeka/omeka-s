<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceTemplateForm;
use Omeka\Mvc\Exception\NotFoundException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ResourceTemplateController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('label');
        $response = $this->api()->search('resource_templates', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('resourceTemplates', $response->getContent());
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('resource_templates', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('resourceTemplate', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('resource_templates', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function deleteConfirmAction()
    {
        $resource = $this->api()->read('resource_templates', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $resource);
        $view->setVariable('resourceLabel', 'resource template');
        $view->setVariable('partialPath', 'omeka/admin/resource-template/show-details');
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('resource_templates', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Resource template could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Resource template successfully deleted');
                }
            } else {
                $this->messenger()->addError('Resource template could not be deleted');
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function addAction()
    {
        return $this->getAddEditView();
    }

    public function editAction()
    {
        return $this->getAddEditView();
    }

    /**
     * Get the add/edit view.
     *
     * @return ViewModel
     */
    protected function getAddEditView()
    {
        $action = $this->params('action');
        $form = new ResourceTemplateForm($this->getServiceLocator());
        $resourceClassId = null;

        if ('edit' == $action) {
            $resourceTemplate = $this->api()
                ->read('resource_templates', $this->params('id'))
                ->getContent();
            $form->setData($resourceTemplate->jsonSerialize());
            if ($resourceTemplate->resourceClass()) {
                $resourceClassId = $resourceTemplate->resourceClass()->id();
            }
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                if ('edit' == $action) {
                    $response = $this->api()->update(
                        'resource_templates', $resourceTemplate->id(), $data
                    );
                } else {
                    $response = $this->api()->create('resource_templates', $data);
                }
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    if ('edit' == $action) {
                        $this->messenger()->addSuccess('Resource template edited.');
                    } else {
                        $this->messenger()->addSuccess('Resource template created.');
                    }
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        if ('edit' == $action) {
            $view->setVariable('resourceTemplate', $resourceTemplate);
            $view->setVariable('confirmForm', new ConfirmForm(
                $this->getServiceLocator(), null, [
                    'button_value' => $this->translate('Confirm Delete'),
                ]
            ));
        }
        $view->setVariable('propertyRows', $this->getPropertyRows());
        $view->setVariable('resourceClassId', $resourceClassId);
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Get the property rows for the add/edit form.
     *
     * @return array
     */
    protected function getPropertyRows()
    {
        $action = $this->params('action');

        // Set POSTed property rows
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $propertyRows = $data['o:resource_template_property'];
            foreach ($propertyRows as $key => $propertyRow) {
                if (!isset($propertyRow['o:property']['o:id'])) {
                    // No property ID indicates that the property was removed.
                    unset($propertyRows[$key]);
                    continue;
                }
                $property = $this->api()->read(
                    'properties', $propertyRow['o:property']['o:id']
                )->getContent();
                $propertyRows[$property->id()]['o:property'] = $property;
            }

        // Set default property rows.
        } else {
            $propertyRows = [];
            if ('edit' == $action) {
                $resourceTemplate = $this->api()
                    ->read('resource_templates', $this->params('id'))
                    ->getContent();
                $resTemProps = $resourceTemplate->resourceTemplateProperties();
                foreach ($resTemProps as $key => $resTemProp) {
                    $propertyRows[$key] = [
                        'o:property' => $resTemProp->property(),
                        'o:alternate_label' => $resTemProp->alternateLabel(),
                        'o:alternate_comment' => $resTemProp->alternateComment(),
                        'o:data_type' => $resTemProp->dataType(),
                    ];
                }
            } else {
                // For the add action, determs:title and dcterms:description are
                // the only default property rows.
                $titleProperty = $this->api()->searchOne(
                    'properties', ['term' => 'dcterms:title']
                )->getContent();
                $descriptionProperty = $this->api()->searchOne(
                    'properties', ['term' => 'dcterms:description']
                )->getContent();
                $propertyRows = [
                    [
                        'o:property' => $titleProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                        'o:data_type' => null,
                    ],
                    [
                        'o:property' => $descriptionProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                        'o:data_type' => null,
                    ],
                ];
            }
        }

        return $propertyRows;
    }

    /**
     * Return a new property row for the add-edit page.
     */
    public function addNewPropertyRowAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new NotFoundException;
        }

        $property = $this->api()
            ->read('properties', $this->params()->fromQuery('property_id'))
            ->getContent();
        $propertyRow = [
            'o:property' => $property,
            'o:alternate_label' => null,
            'o:alternate_comment' => null,
            'o:data_type' => null,
        ];

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/resource-template/show-property-row');
        $view->setVariable('propertyRow', $propertyRow);
        return $view;
    }
}
