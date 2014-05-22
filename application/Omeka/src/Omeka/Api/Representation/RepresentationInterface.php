<?php
namespace Omeka\Api\Representation;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Stdlib\JsonSerializable;

/**
 * The representation interface
 *
 * A representation wraps around and provides a standard interface to API data.
 * It has two major functions:
 *
 *   - Serialize into a JSON-LD object
 *   - Pass around internally as a rich data object
 */
interface RepresentationInterface extends
    JsonSerializable,
    ServiceLocatorAwareInterface
{
    /**
     * Set the data.
     *
     * The data is the information from which to derive a representation.
     *
     * @param mixed $data
     */
    public function setData($data);
}
