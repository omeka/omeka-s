<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractRepresentation implements RepresentationInterface
{
    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Construct the resource representation object.
     *
     * @param string|int $id The unique identifier of this resource
     * @param mixed $data The data from which to derive a representation
     * @param ServiceLocatorInterface $adapter The corresponsing adapter
     */
    public function __construct($id, $data, AdapterInterface $adapter) {
        $this->setId($id);
        $this->setData($data);
        $this->setAdapter($adapter);
        $this->setServiceLocator($adapter->getServiceLocator());
    }

    /**
     * Set the unique resource identifier.
     *
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the unique resource identifier.
     *
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function setData($data)
    {
        $this->validateData($data);
        $this->data = $data;
    }

    /**
     * Validate the data.
     *
     * When the data needs to be validated, override this method and throw an
     * exception when the data is invalid for the representation.
     *
     * @param mixed $data
     */
    public function validateData($data)
    {}

    /**
     * Set the corresponding adapter.
     *
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Get the corresponding adapter or another adapter by resource name.
     *
     * @param null|string $resourceName
     * @return AdapterInterface
     */
    public function getAdapter($resourceName = null)
    {
        if (is_string($resourceName)) {
            return $this->getServiceLocator()
                ->get('Omeka\ApiAdapterManager')
                ->get($resourceName);
        }
        return $this->adapter;
    }

    /**
     * Get a reference representation.
     *
     * @param string $resourceName The name of the referenced API resource
     * @param string|int $id The unique identifier of the referenced resource
     * @param mixed $data The data from which to derive the reference
     * @return RepresentationInterface
     */
    public function getReference($id, $data, $adapter)
    {
        // Do not attempt to compose a null reference.
        if (null === $data) {
            return null;
        }

        if ($data instanceof EntityInterface) {
            // An entity reference
            $representationClass = 'Omeka\Api\Represenation\Entity\Representation';
        } else {
            // A generic reference
            $representationClass = 'Omeka\Api\Represenation\Representation';
        }

        return new $representationClass($id, $data, $adapter);
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
