<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Request;
use Zend\EventManager\EventManagerInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract API adapter.
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

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
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the search operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * Create operation stub.
     */
    public function create($data = null)
    {
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the create operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * Batch create operation stub.
     */
    public function batchCreate($data = null)
    {
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the batch create operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * Read operation stub.
     */
    public function read($id, $data = null)
    {
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the read operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * Update operation stub.
     */
    public function update($id, $data = null)
    {
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the update operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * Delete operation stub.
     */
    public function delete($id, $data = null)
    {
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the delete operation.'
            ),
            get_called_class()
        ));
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
     * Get the translator service
     *
     * return TranslatorInterface
     */
    public function getTranslator()
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getServiceLocator()->get('MvcTranslator');
        }
        return $this->translator;
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
