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

            $titleProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:title')
            )->getContent();
            $descriptionProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:description')
            )->getContent();

            // Remove dcterms:title and dcterms:description from data if they
            // have no alternate label and comment.
            foreach ($data['o:resource_template_property'] as $key => $propertyRow) {
                if (!in_array(
                    $propertyRow['o:property']['o:id'],
                    array($titleProperty->id(), $descriptionProperty->id())
                )) {
                    continue;
                }
                if (!$propertyRow['o:alternate_label'] && !$propertyRow['o:alternate_comment']) {
                    unset($data['o:resource_template_property'][$key]);
                }
            }

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

            $titleProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:title')
            )->getContent();
            $descriptionProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:description')
            )->getContent();

            // Remove dcterms:title and dcterms:description from data if they
            // have no alternate label and comment.
            foreach ($data['o:resource_template_property'] as $key => $propertyRow) {
                if (!in_array(
                    $propertyRow['o:property']['o:id'],
                    array($titleProperty->id(), $descriptionProperty->id())
                )) {
                    continue;
                }
                if (!$propertyRow['o:alternate_label'] && !$propertyRow['o:alternate_comment']) {
                    unset($data['o:resource_template_property'][$key]);
                }
            }

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

            $titleProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:title')
            )->getContent();
            $descriptionProperty = $this->api()->searchOne(
                'properties', array('term' => 'dcterms:description')
            )->getContent();

            // When adding and editing a resource template, dcterms:title and
            // dcterms:description must be at the beginning of the form, even if
            // they are not specifically used by the template. Set up the
            // default title and description rows here.
            $titleRow = array(
                'o:property' => $titleProperty,
                'o:alternate_label' => null,
                'o:alternate_comment' => null,
            );
            $descriptionRow = array(
                'o:property' => $descriptionProperty,
                'o:alternate_label' => null,
                'o:alternate_comment' => null,
            );

            if ('add' == $action) {
                // For the add action, title and description are the only
                // default property rows.
                $propertyRows = array($titleRow, $descriptionRow);
           } elseif ('edit' == $action) {
                $resourceTemplate = $this->api()
                    ->read('resource_templates', $this->params('id'))
                    ->getContent();
                $propertyRows = $resourceTemplate->resourceTemplateProperties();
                foreach ($propertyRows as $key => $propertyRow) {
                    // Convert references to full representations.
                    $propertyRows[$key]['o:property'] = $propertyRow['o:property']->getRepresentation();
                    // Remove title and description to be prepended later.
                    if ($titleProperty->id() === $propertyRow['o:property']->id()) {
                        $titleRow = $propertyRows[$key];
                        unset($propertyRows[$key]);
                    } elseif ($descriptionProperty->id() === $propertyRow['o:property']->id()) {
                        $descriptionRow = $propertyRows[$key];
                        unset($propertyRows[$key]);
                    }
                }
                // Prepend title and description at the beginning of the rows.
                array_unshift($propertyRows, $titleRow, $descriptionRow);
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
