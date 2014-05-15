<?php
namespace Omeka\Api\Representation;

use Zend\Stdlib\JsonSerializable;

interface RepresentationInterface extends JsonSerializable
{
    /**
     * Set the representation data
     *
     * @param mixed $data The information from which to derive a representation
     * of the resource.
     */
    public function setData($data);

    /**
     * Serialize the resource as an array
     *
     * @return mixed
     */
    public function toArray();
}
