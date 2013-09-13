<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract API adapter.
 */
abstract class AbstractAdapter implements AdapterInterface, ServiceLocatorAwareInterface
{
    protected $data = array();
    
    protected $services;
    
    public function search()
    {
        throw new Exception\RuntimeException('The adapter does not implement the search function.');
    }
    
    public function create()
    {
        throw new Exception\RuntimeException('The adapter does not implement the create function.');
    }
    
    public function read()
    {
        throw new Exception\RuntimeException('The adapter does not implement the read function.');
    }
    
    public function update()
    {
        throw new Exception\RuntimeException('The adapter does not implement the update function.');
    }
    
    public function delete()
    {
        throw new Exception\RuntimeException('The adapter does not implement the delete function.');
    }
    
    public function setData(array $data)
    {
        $this->data = $data;
    }
    
    public function getData($key = null)
    {
        if (null === $key) {
            return $this->data;
        }
        if (!array_key_exists($key, $this->data)) {
            throw new Exception\RuntimeException(sprintf('"%s" is an invalid data key.', $key));
        }
        return $this->data[$key];
    }
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->services;
    }
}
