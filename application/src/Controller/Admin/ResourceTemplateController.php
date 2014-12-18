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
        $propertyRows = array();

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();

            // Set passed property rows
            $propertyRows = $data['o:resource_template_property'];
            foreach ($propertyRows as $propertyRow) {
                $property = $this->api()->read(
                    'properties', $propertyRow['o:property']['o:id']
                )->getContent();
                $propertyRows[$property->id()]['o:property'] = $property;
            }

            // @todo Remove dcterms:title and dcterms:description from data if
            // they have no alternate label and comment.

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

        } else {
            // Set default property rows.
            foreach (array('dcterms:title', 'dcterms:description') as $term) {
                $property = $this->api()->searchOne(
                    'properties', array('term' => $term)
                )->getContent();
                $propertyRows[$property->id()]['o:property'] = $property;
                $propertyRows[$property->id()]['o:alternate_label'] = null;
                $propertyRows[$property->id()]['o:alternate_comment'] = null;
            }
        }

        $view = new ViewModel;
        $view->setVariable('propertyRows', $propertyRows);
        $view->setVariable('form', $form);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Removal'),
            )
        ));
        return $view;
    }

    public function editAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function showPropertyRowAction()
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
        $view->setVariable('propertyRow', $propertyRow);
        return $view;
    }
}
