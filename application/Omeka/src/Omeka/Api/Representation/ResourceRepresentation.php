<?php
namespace Omeka\Api\Representation;

use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceRepresentation extends AbstractRepresentation
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * Construct the resource representation object.
     *
     * @param string $resourceName The name of the represented resource
     * @param mixed $data The data from which to derive the representation
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($resourceName, $data,
        ServiceLocatorInterface $serviceLocator
    ) {
        $this->setResourceName($resourceName);
        $this->setData($data);
        $this->setServiceLocator($serviceLocator);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return array(
            '@id' => $adapter->getApiUrl($this->getData()),
        );
    }

    /**
     * Set the resource name.
     *
     * @param $resourceName
     */
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;
    }

    /**
     * Get the resource name.
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }
}
