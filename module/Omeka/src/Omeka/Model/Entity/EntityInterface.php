<?php
namespace Omeka\Model\Entity;

interface EntityInterface
{
    public function setData($data);
    public function toArray();
}
