<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Representation\EntityRepresentation;
use Omeka\Api\Representation\ResourceRepresentation;
use Omeka\Api\Request;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\DateTime;
use Zend\EventManager\EventManagerInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract API adapter.
 */
abstract class AbstractAdapter implements AdapterInterface
{
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
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the search operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function create(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the create operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the batch create operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the read operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function update(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the update operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        throw new Exception\OperationNotImplementedException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the delete operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getApiUrl($data)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebUrl($data)
    {
        return null;
    }

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
     * Get a reference representation.
     *
     * @param string $resourceName The name of the referenced API resource
     * @param mixed $data The data from which to derive the reference
     * @return RepresentationInterface
     */
    public function getReference($resourceName, $data)
    {
        // Do not attempt to compose a null reference.
        if (null === $data) {
            return null;
        }

        if ($data instanceof EntityInterface) {
            // An entity reference
            return new EntityRepresentation(
                $resourceName, $data, $this->getServiceLocator()
            );
        } else {
            // A generic reference
            return new ResourceRepresentation(
                $resourceName, $data, $this->getServiceLocator()
            );
        }
    }

    /**
     * Get a JSON serializable instance of DateTime.
     *
     * @param \DateTime $dateTime
     * @return DateTime
     */
    public function getDateTime(\DateTime $dateTime)
    {
        return new DateTime($dateTime);
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
