<?php
namespace Omeka\Controller\Api;

use Omeka\Api\Request;
use Omeka\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractRestfulController
{
    public function indexAction()
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $resource = $this->params()->fromRoute('resource');
        $request = new Request(Request::READ, $resource);
        $request->setId(1);
        $request->setData('data_in');
        $response = $manager->execute($request);
        print_r($response);
        return new ViewModel();
    }
}
