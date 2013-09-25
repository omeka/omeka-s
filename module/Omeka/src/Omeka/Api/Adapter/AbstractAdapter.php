<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract API adapter.
 */
abstract class AbstractAdapter implements AdapterInterface, ServiceLocatorAwareInterface
{
    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Search operation stub.
     */
    public function search($data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the search function.'
        );
    }

    /**
     * Create operation stub.
     */
    public function create($data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the create function.'
        );
    }

    /**
     * Read operation stub.
     */
    public function read($id, $data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the read function.'
        );
    }

    /**
     * Update operation stub.
     */
    public function update($id, $data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the update function.'
        );
    }

    /**
     * Delete operation stub.
     */
    public function delete($id, $data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the delete function.'
        );
    }

    /**
     * Set adapter data.
     *
     * Override this method to validate data specific to the adapter.
     * 
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get adapter data.
     * 
     * @param null|string $key
     * @return mixed
     */
    public function getData($key = null)
    {
        if (null === $key) {
            return $this->data;
        }
        if (!array_key_exists($key, $this->data)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" is an invalid data key.', 
                $key
            ));
        }
        return $this->data[$key];
    }

    /**
     * Set the service locator.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }

    /**
     * Get the service locator.
     * 
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}
