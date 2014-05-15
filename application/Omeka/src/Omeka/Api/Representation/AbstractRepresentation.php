<?php
namespace Omeka\Api\Representation;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractRepresentation implements
    RepresentationInterface,
    ServiceLocatorAwareInterface
{
    /**
     * @var string
     */
    private $resourceName;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var ServiceLocatorInterface
     */
    private $services;

    /**
     * Construct the representation object.
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

    /**
     * Validate and set the data.
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->validateData($data);
        $this->data = $data;
    }

    /**
     * Get the data.
     *
     * Note that, to ensure encapsulation, the data is not externally
     * accessable.
     *
     * @return mixed
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Validate the passed data.
     *
     * When the data needs to be validated, override this method and throw an
     * exception when the data is invalid for the representation.
     *
     * @param mixed $data
     */
    public function validateData($data)
    {}

    /**
     * Get an adapter from the API adapter manager.
     *
     * @param string $resourceName
     * @return AdapterInterface
     */
    public function getAdapter($resourceName)
    {
        return $this->getServiceLocator()
            ->get('Omeka\ApiAdapterManager')
            ->get($resourceName);
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
