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
    public function search()
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the search function.'
        );
    }

    /**
     * Create operation stub.
     */
    public function create()
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the create function.'
        );
    }

    /**
     * Read operation stub.
     */
    public function read($id)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the read function.'
        );
    }

    /**
     * Update operation stub.
     */
    public function update($id)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the update function.'
        );
    }

    /**
     * Delete operation stub.
     */
    public function delete($id)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the delete function.'
        );
    }

    /**
     * Set adapter data.
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
     * @return array|string
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
