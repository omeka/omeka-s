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
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('resource_templates', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('resourceTemplate', $response->getContent());
        return $view;
    }

    public function addAction()
    {
        $form = new ResourceTemplateForm($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            // @todo Remove dcterms:title and :description from data if they
            // have no alternate label and comment.

            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api()->create('resource_templates', $data);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Resource template created.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setTemplate('omeka/admin/resource-template/add-edit');
        $view->setVariable('propertyRows', $this->getPropertyRows());
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $form = new ResourceTemplateForm($this->getServiceLocator());
        $resourceTemplate = $this->api()
            ->read('resource_templates', $this->params('id'))
            ->getContent();
        $form->setData($resourceTemplate->jsonSerialize());

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            // @todo Remove dcterms:title and :description from data if they
            // have no alternate label and comment.

            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api()->update(
                    'resource_templates', $resourceTemplate->id(), $data
                );
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Resource template edited.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setTemplate('omeka/admin/resource-template/add-edit');
        $view->setVariable('propertyRows', $this->getPropertyRows());
        $view->setVariable('form', $form);
        return $view;
    }

    protected function getPropertyRows()
    {
        $propertyRows = array();
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

            // Set the dcterms:title and :description properties.
            $titleProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:title')
            )->getContent();
            $descriptionProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:description')
            )->getContent();

            if ('add' == $action) {
                // For the add action, dcterms:title and dcterms:description are
                // the only default property rows.
                foreach (array($titleProperty, $descriptionProperty) as $property) {
                    $propertyRows[$property->id()]['o:property'] = $property;
                    $propertyRows[$property->id()]['o:alternate_label'] = null;
                    $propertyRows[$property->id()]['o:alternate_comment'] = null;
                }
           } elseif ('edit' == $action) {

                // @todo For the edit action, put dcterms:title and :description
                // at the beginning of the rows, and the rest following.

                $resourceTemplate = $this->api()
                    ->read('resource_templates', $this->params('id'))
                    ->getContent();
                $propertyRows = $resourceTemplate->resourceTemplateProperties();
                foreach ($propertyRows as &$propertyRow) {
                    // Convert references to full representations.
                    $propertyRow['o:property'] = $propertyRow['o:property']->getRepresentation();
                }
            } else {
                // @todo Illegal action, throw exception
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
