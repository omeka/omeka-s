<?php
namespace Omeka\Controller;

use Omeka\Mvc\Exception;

class ApiLocalController extends ApiController
{
    public function create($data, $fileData = [])
    {
        throw new Exception\NotFoundException('The create operation is not supported.');
    }

    public function update($id, $data)
    {
        throw new Exception\NotFoundException('The update operation is not supported.');
    }

    public function patch($id, $data)
    {
        throw new Exception\NotFoundException('The patch operation is not supported.');
    }

    public function delete($id)
    {
        throw new Exception\NotFoundException('The delete operation is not supported.');
    }
}
