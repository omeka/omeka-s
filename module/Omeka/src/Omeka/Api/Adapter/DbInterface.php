<?php
namespace Omeka\Api\Adapter;

interface DbInterface
{
    public function getEntityClass();
    public function setData($entity, array $data);
    public function toArray($entity);
}
