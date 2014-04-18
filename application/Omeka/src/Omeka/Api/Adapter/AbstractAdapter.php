<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Reference\ReferenceInterface;
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
        throw new Exception\RuntimeException(sprintf(
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
        throw new Exception\RuntimeException(sprintf(
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
        throw new Exception\RuntimeException(sprintf(
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
        throw new Exception\RuntimeException(sprintf(
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
        throw new Exception\RuntimeException(sprintf(
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
        throw new Exception\RuntimeException(sprintf(
            $this->getTranslator()->translate(
                'The %1$s adapter does not implement the delete operation.'
            ),
            get_called_class()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getApiUrl($entity)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebUrl($entity)
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
     * Get a reference to a resource.
     *
     * @see ReferenceInterface
     * @param mixed $data The information from which to derive a representation
     * of the resource.
     * @param AdapterInterface $adapter The corresponding resource adapter.
     * @param null|string $referenceClass The name of the reference class. If an
     * Doctrine entity is passed as $data, an Entity reference is composed,
     * otherwise, when null, a generic reference is composed.
     * @return ReferenceInterface
     */
    public function getReference($data, AdapterInterface $adapter, $referenceClass = null)
    {
        $t = $this->getTranslator();

        if (null === $data) {
            // Do not attempt to compose a null reference
            return null;
        }

        if (is_string($referenceClass)) {
            // Validate the reference class.
            if (!class_exists($referenceClass)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    $t->translate('Resource reference class %s does not exist.'),
                    $referenceClass
                ));
            }
            if (!is_subclass_of($referenceClass, ReferenceInterface)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    $t->translate('Invalid resource reference class %s.'),
                    $referenceClass
                ));
            }
        } elseif ($data instanceof EntityInterface) {
            // An entity reference
            $referenceClass = 'Omeka\Api\Reference\Entity';
        } else {
            // A generic reference
            $referenceClass = 'Omeka\Api\Reference\Reference';
        }

        $reference = new $referenceClass;
        $reference->setData($data);
        $reference->setAdapter($adapter);
        return $reference;
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
