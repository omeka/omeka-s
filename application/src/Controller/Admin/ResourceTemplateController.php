<?php 
namespace Omeka\Controller\Admin;

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
        $view = new ViewModel;
        return $view;
    }

    public function addAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function editAction()
    {
        $view = new ViewModel;
        return $view;
    }
}
