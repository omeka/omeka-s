<?php
namespace Omeka\Api\Representation;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractRepresentation implements
    RepresentationInterface,
    ServiceLocatorAwareInterface
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var ServiceLocatorInterface
     */
    private $services;

    /**
     * Return the data as the representation array.
     *
     * Override this method if the data needs to be transformed.
     *
     * @return mixed
     */
    public function toArray()
    {
        return $this->data;
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
