<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\DateTime;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Abstract representation.
 *
 * Provides functionality for all representations.
 */
abstract class AbstractRepresentation implements RepresentationInterface
{
    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * Serialize the data to a JSON-LD compatible format.
     *
     * This method is provided by the JsonSerializable interface and is
     * implemented here for documentation purposes only.
     *
     * @return array
     */
    abstract public function jsonSerialize();

    /**
     * Validate and set the data.
     *
     * {@inheritDoc}
     */
    public function setData($data)
    {
        $this->validateData($data);
        $this->data = $data;
    }

    /**
     * Get the data.
     *
     * To ensure encapsulation and prevent unwanted modifications, the data is
     * not directly accessible outside this scope.
     *
     * @return mixed
     */
    protected function getData()
    {
        return $this->data;
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
     * Get an adapter by resource name.
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
     * @param string|int $id The unique identifier of the referenced resource
     * @param mixed $data The data from which to derive the reference
     * @param AdapterInterface $adapter The corresponding API adapter
     * @return RepresentationInterface
     */
    public function getReference($id, $data, AdapterInterface $adapter)
    {
        // Do not attempt to compose a null reference.
        if (null === $data) {
            return null;
        }

        if ($data instanceof EntityInterface) {
            // An entity reference
            $id = $data->getId();
            $representationClass = 'Omeka\Api\Representation\Entity\EntityReference';
        } else {
            // A generic reference
            $representationClass = 'Omeka\Api\Representation\ResourceReference';
        }

        return new $representationClass($id, $data, $adapter);
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
     * @return TranslatorInterface
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
}
