<?php 
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'vocabulary',
            'action' => 'browse',
        ));
    }

    public function showAction()
    {
        $view = new ViewModel;
        $id = $this->params('id');
        $response = $this->api()->read('vocabularies', $id);
        if ($this->apiError($response) === false) {
            $view->setVariable('vocabulary', $response->getContent());
        }
        $view->setVariable('vocabulary', $response->getContent());
        return $view;
    }


    public function browseAction()
    {
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('vocabularies', $query);
        if ($this->apiError($response) === false) {
            $view->setVariable('vocabularies', $response->getContent());
        }
        $view->setVariable('vocabularies', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $response = $this->api()->read(
            'vocabularies', array('id' => $this->params('id'))
        );
        $view->setVariable('vocabulary', $response->getContent());
        return $view;
    }
}