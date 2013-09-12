<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception as ApiException;

class Db
{
    public function __construct(array $data)
    {
        if (!isset($data['entity_name'])) {
            throw new ApiException('An entity name is not registered.');
        }
    }
}
