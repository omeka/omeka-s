<?php
namespace Omeka\Controller;

use Omeka\Api\Response;
use Omeka\Api\Request;
use Omeka\View\Model\ApiJsonModel;
use Zend\Mvc\Controller\AbstractRestfulController;

class ApiController extends AbstractRestfulController
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
        return new ApiJsonModel($response);
    }

    public function getList()
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::SEARCH,
            $this->params()->fromRoute('resource')
        );
        $request->setContent($this->params()->fromQuery());
        $response = $manager->execute($request);
        return new ApiJsonModel($response);
    }

    public function create($data)
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::CREATE,
            $this->params()->fromRoute('resource')
        );
        $request->setContent($this->processBodyContent($this->getRequest()));
        $response = $manager->execute($request);
        return new ApiJsonModel($response);
    }

    public function update($id, $data)
    {
        $manager = $this->getServiceLocator()->get('ApiManager');
        $request = new Request(
            Request::UPDATE,
            $this->params()->fromRoute('resource')
        );
        $request->setId($id);
        $request->setContent($data);
        $response = $manager->execute($request);
        return new ApiJsonModel($response);
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
        return new ApiJsonModel($response);
    }
}
