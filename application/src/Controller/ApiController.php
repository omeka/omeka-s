<?php
namespace Omeka\Controller;

use Omeka\View\Model\ApiJsonModel;

class ApiController extends AbstractApiController
{
    public function create($data, $fileData = [])
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->create($resource, $data, $fileData);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    public function update($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->update($resource, $id, $data);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    public function patch($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->update($resource, $id, $data, [], ['isPartial' => true]);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    public function delete($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->delete($resource, $id);
        return new ApiJsonModel($response, $this->getViewOptions());
    }
}
