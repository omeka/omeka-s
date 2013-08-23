<?php
namespace Omeka\Controller\Api;

use Omeka\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractRestfulController
{
    public function indexAction()
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        return new ViewModel();
    }
}
