<?php
namespace Omeka\Api\Representation;

use Zend\ServiceManager\ServiceLocatorInterface;

class NonResourceRepresentation extends AbstractRepresentation
{
    /**
     * Construct the non-resource representation object.
     *
     * @param mixed $data The data from which to derive the representation
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($data, ServiceLocatorInterface $serviceLocator)
    {
        $this->setData($data);
        $this->setServiceLocator($serviceLocator);
    }

    /**
     * Serialize as a simple JSON-LD object.
     *
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array();
    }
}
