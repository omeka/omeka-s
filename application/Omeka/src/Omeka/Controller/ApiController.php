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
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->read($resource, $id);
        return new ApiJsonModel($response);
    }

    public function getList()
    {
        $resource = $this->params()->fromRoute('resource');
        $data = $this->params()->fromQuery();
        $response = $this->api()->search($resource, $data);
        return new ApiJsonModel($response);
    }

    public function create($data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->create($resource, $data);
        return new ApiJsonModel($response);
    }

    public function update($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->update($resource, $id, $data);
        return new ApiJsonModel($response);
    }

    public function delete($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->delete($resource, $id);
        return new ApiJsonModel($response);
    }
}
