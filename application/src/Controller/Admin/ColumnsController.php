<?php
namespace Omeka\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class ColumnsController extends AbstractActionController
{
    public function columnListAction()
    {
        $context = $this->params()->fromQuery('context');
        $resourceType = $this->params()->fromQuery('resource_type');
        $userId = $this->params()->fromQuery('user_id');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('context', $context);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('userId', $userId);
        return $view;
    }

    public function columnRowAction()
    {
        $resourceType = $this->params()->fromQuery('resource_type');
        $userId = $this->params()->fromQuery('user_id');
        $columnData = $this->params()->fromQuery('column_data');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('userId', $userId);
        $view->setVariable('columnData', $columnData);
        return $view;
    }

    public function columnEditSidebarAction()
    {
        $resourceType = $this->params()->fromQuery('resource_type');
        $userId = $this->params()->fromQuery('user_id');
        $columnData = $this->params()->fromQuery('column_data');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('userId', $userId);
        $view->setVariable('columnData', $columnData);
        return $view;
    }
}
