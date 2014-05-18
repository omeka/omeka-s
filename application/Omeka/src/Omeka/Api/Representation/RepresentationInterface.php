<?php
namespace Omeka\Api\Representation;

use Zend\Stdlib\JsonSerializable;

/**
 * The representation interface
 *
 * A representation wraps around and provides a standard interface to data. It
 * has two major functions: 1) it can be serialized into a JSON-LD object,
 * typically for API consumers; and 2) it can be passed around internally as a
 * rich data object.
 */
interface RepresentationInterface extends JsonSerializable
{
    /**
     * Set the data.
     *
     * The data is the information from which to derive a representation.
     *
     * @param mixed $data
     */
    public function setData($data);

    /**
     * Serialize the data as an array.
     *
     * @return mixed
     */
    public function toArray();
}
