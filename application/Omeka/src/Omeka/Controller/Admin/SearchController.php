<?php
namespace Omeka\Controller\Admin;

use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SearchController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        return $view;
    }
}
