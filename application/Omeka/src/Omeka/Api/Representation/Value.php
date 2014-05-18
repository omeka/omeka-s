<?php
namespace Omeka\Api\Representation;

use Omeka\model\Entity\Value as ValueEntity;

class Value extends NonResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        if (ValueEntity::TYPE_RESOURCE == $this->getValueType()) {
            $valueResource = $this->getData()->getValueResource();
            $valueResourceAdapter = $this->getServiceLocator()
                ->get('Omeka\ApiAdapterManager')
                ->get($valueResource->getResourceName());
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
                $valueResourceAdapter = $this->getAdapter($valueResource->getResourceName());
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
