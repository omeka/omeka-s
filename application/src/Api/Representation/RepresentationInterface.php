<?php
namespace Omeka\Api\Representation;

use Zend\Stdlib\JsonSerializable;

/**
 * The representation interface
 *
 * A representation wraps around and provides a standard interface to data. It
 * has two primary functions:
 *
 *   - Serialize into a JSON-LD object
 *   - Pass around internally as a rich, read-only data object
 */
interface RepresentationInterface extends JsonSerializable
{
    /**
     * Serialize the data to a JSON-LD compatible format.
     *
     * @link http://www.w3.org/TR/json-ld/
     * @return array
     */
    public function jsonSerialize();
}
