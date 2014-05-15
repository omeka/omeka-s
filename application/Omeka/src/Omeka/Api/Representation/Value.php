<?php
namespace Omeka\Api\Representation;

use Omeka\model\Entity\Value as ValueEntity;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Value implements RepresentationInterface, ServiceLocatorAwareInterface
{
    /**
     * @var ValueEntity
     */
    private $value;

    /**
     * @var ServiceLocatorInterface
     */
    private $services;

    /**
     * Construct the representation object for a value.
     */
    public function __construct(ValueEntity $value, ServiceLocatorInterface $serviceLocator)
    {
        $this->setData($value);
        $this->setServiceLocator($serviceLocator);
    }

    /**
     * {@inheritDoc}
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the value.
     *
     * Note that, to ensure encapsulation, the value is not externally
     * accessable.
     *
     * @return ValueEntity
     */
    protected function getData()
    {
        return $this->data;
    }

    /**
     * Get the value type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->getData()->getType();
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        if (ValueEntity::TYPE_RESOURCE == $this->getType()) {
            $valueResource = $this->getData()->getValueResource();
            $valueResourceAdapter = $this->getServiceLocator()
                ->get('Omeka\ApiAdapterManager')
                ->get($valueResource->getResourceName());
            return $valueResourceAdapter->extract($valueResource);
        }
        return $this->jsonSerialize();
    }

    /**
     * Extract a single value entity.
     *
     * @return array JSON-LD value object
     */
    public function jsonSerialize()
    {
        $value = $this->getData();
        $valueObject = array();

        switch ($this->getType()) {

            case ValueEntity::TYPE_RESOURCE:
                $valueResource = $value->getValueResource();
                $valueResourceAdapter = $this->services
                    ->get('Omeka\ApiAdapterManager')
                    ->get($valueResource->getResourceName());
                $valueObject['@id'] = $valueResourceAdapter->getApiUrl($valueResource);
                $valueObject['value_resource_id'] = $valueResource->getId();
                break;

            case ValueEntity::TYPE_URI:
                $valueObject['@id'] = $value->getValue();
                break;

            case ValueEntity::TYPE_LITERAL:
            default:
                $valueObject['@value'] = $value->getValue();
                if ($value->getLang()) {
                    $valueObject['@language'] = $value->getLang();
                }
                $valueObject['is_html'] = $value->getIsHtml();
                break;
        }

        $valueObject['value_id'] = $value->getId();
        $valueObject['property_id'] = $value->getProperty()->getId();
        $valueObject['property_label'] = $value->getProperty()->getLabel();

        return $valueObject;
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
