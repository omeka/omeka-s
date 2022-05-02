<?php
namespace Omeka\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class ColumnsController extends AbstractActionController
{
    public function columnsAction()
    {
        $resourceType = $this->params()->fromPost('resource_type');
        $userId = $this->params()->fromPost('user_id');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('userId', $userId);
        return $view;
    }

    public function columnAction()
    {
        $resourceType = $this->params()->fromPost('resource_type');
        $userId = $this->params()->fromPost('user_id');
        $columnData = $this->params()->fromPost('column_data');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('userId', $userId);
        $view->setVariable('columnData', $columnData);
        return $view;
    }

    public function sidebarAction()
    {
        $resourceType = $this->params()->fromPost('resource_type');
        $userId = $this->params()->fromPost('user_id');
        $columnData = $this->params()->fromPost('column_data');

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceType', $resourceType);
        $view->setVariable('userId', $userId);
        $view->setVariable('columnData', $columnData);
        return $view;
    }
}
