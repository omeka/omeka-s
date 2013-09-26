<?php
namespace Omeka\Model\Entity;

interface EntityInterface
{
    public function setData(array $data);
    public function toArray();
}
