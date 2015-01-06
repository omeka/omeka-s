<?php 
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceTemplateForm;
use Omeka\Mvc\Exception\NotFoundException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ResourceTemplateController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'resource-template',
            'action' => 'browse',
        ));
    }

    public function browseAction()
    {
        $view = new ViewModel;

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('resource_templates', $query);

        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('resourceTemplates', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
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
        $view->setVariable('resourceTemplate', $response->getContent());
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
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
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'resource-template',
            'action'     => 'browse',
        ));
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
        $view->setTemplate('omeka/admin/resource-template/add-edit');
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
            $propertyRows = array();
            if ('edit' == $action) {
                $resourceTemplate = $this->api()
                    ->read('resource_templates', $this->params('id'))
                    ->getContent();
                $propertyRows = $resourceTemplate->resourceTemplateProperties();
                foreach ($propertyRows as $key => $propertyRow) {
                    // Convert references to full representations.
                    $propertyRows[$key]['o:property'] = $propertyRow['o:property']->getRepresentation();
                }
            } else {
                // For the add action, determs:title and dcterms:description are
                // the only default property rows.
                $titleProperty = $this->api()->searchOne(
                    'properties', array('term' => 'dcterms:title')
                )->getContent();
                $descriptionProperty = $this->api()->searchOne(
                    'properties', array('term' => 'dcterms:description')
                )->getContent();
                $propertyRows = array(
                    array(
                        'o:property' => $titleProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                    ),
                    array(
                        'o:property' => $descriptionProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                    ),
                );
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
        $propertyRow = array(
            'o:property' => $property,
            'o:alternate_label' => null,
            'o:alternate_comment' => null,
        );

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/resource-template/show-property-row');
        $view->setVariable('propertyRow', $propertyRow);
        return $view;
    }
}
