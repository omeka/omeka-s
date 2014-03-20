<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Request;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract API adapter.
 */
abstract class AbstractAdapter implements
    AdapterInterface,
    ServiceLocatorAwareInterface,
    EventManagerAwareInterface,
    ResourceInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Search operation stub.
     */
    public function search($data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the search operation.'
        );
    }

    /**
     * Create operation stub.
     */
    public function create($data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the create operation.'
        );
    }

    /**
     * Batch create operation stub.
     */
    public function batchCreate($data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the batch create operation.'
        );
    }

    /**
     * Read operation stub.
     */
    public function read($id, $data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the read operation.'
        );
    }

    /**
     * Update operation stub.
     */
    public function update($id, $data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the update operation.'
        );
    }

    /**
     * Delete operation stub.
     */
    public function delete($id, $data = null)
    {
        throw new Exception\RuntimeException(
            'The adapter does not implement the delete operation.'
        );
    }

    /**
     * Set the API request.
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the API request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
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
    
    /**
     * {@inheritDoc}
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(get_called_class());
        $this->events = $events;
    }

    /**
     * {@inheritDoc}
     */
    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager($this->getServiceLocator()->get('EventManager'));
        }
        return $this->events;
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {
        return get_called_class();
    }
}
