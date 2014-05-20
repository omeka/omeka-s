<?php
namespace Omeka\Api\Representation;

use Omeka\Model\Entity\Value as ValueEntity;
use Zend\ServiceManager\ServiceLocatorInterface;

class ValueRepresentation extends AbstractRepresentation
{
    /**
     * Construct the value representation object.
     *
     * @param mixed $data
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct($data, ServiceLocatorInterface $serviceLocator)
    {
        $this->setData($data);
        $this->setServiceLocator($serviceLocator);
    }

    /**
     * {@inheritDoc}
     */
    public function extract()
    {
        if (ValueEntity::TYPE_RESOURCE == $this->getValueType()) {
            $valueResource = $this->getData()->getValueResource();
            $valueResourceAdapter = $this->getAdapter($valueResource->getResourceName());
            return $valueResourceAdapter->extract($valueResource)->toArray();
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

        switch ($this->getValueType()) {

            case ValueEntity::TYPE_RESOURCE:
                $valueResource = $value->getValueResource();
                $valueResourceAdapter = $this->getServiceLocator()
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
     * Get the value type.
     *
     * @return string
     */
    public function getValueType()
    {
        return $this->getData()->getType();
    }
}
