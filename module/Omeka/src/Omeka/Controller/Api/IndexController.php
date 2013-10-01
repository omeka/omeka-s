<?php
namespace Omeka\Controller\Api;

use Omeka\Api\Request;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractRestfulController
{
    public function get($id)
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::READ,
            $this->params()->fromRoute('resource')
        );
        $request->setId($id);
        $response = $manager->execute($request);
        print_r($response);exit;
    }

    public function getList()
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::SEARCH,
            $this->params()->fromRoute('resource')
        );
        $response = $manager->execute($request);
        print_r($response);exit;
    }

    public function create($data)
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::CREATE,
            $this->params()->fromRoute('resource')
        );
        $request->setData($this->processBodyContent($this->getRequest()));
        $response = $manager->execute($request);
        print_r($response);exit;
    }

    public function update($id, $data)
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::UPDATE,
            $this->params()->fromRoute('resource')
        );
        $request->setId($id);
        $request->setData($data);
        $response = $manager->execute($request);
        print_r($response);exit;
    }

    public function delete($id)
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::DELETE,
            $this->params()->fromRoute('resource')
        );
        $request->setId($id);
        $response = $manager->execute($request);
        print_r($response);exit;
    }
}
