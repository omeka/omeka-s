<?php
namespace Omeka\Api\Representation;

use Omeka\Model\Entity\Value;
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
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->setData($data);
    }

    /**
     * Cast the value representation as a string.
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->getType()) {

            case Value::TYPE_RESOURCE:
                $valueResource = $this->getData()->getValueResource();
                $valueResourceAdapter = $this->getAdapter(
                    $valueResource->getResourceName()
                );
                return $valueResourceAdapter->getApiUrl($valueResource);

            case Value::TYPE_URI:
            case Value::TYPE_LITERAL:
            default:
                return $this->getData()->getValue();
        }
    }

    /**
     * @var array
     */
    public function validateData($data)
    {
        if (!$data instanceof Value) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(sprintf(
                    'Invalid data sent to %s.', get_called_class()
                ))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $value = $this->getData();
        $valueObject = array();

        switch ($this->getType()) {

            case Value::TYPE_RESOURCE:
                $valueResource = $this->getData()->getValueResource();
                $valueResourceAdapter = $this->getAdapter(
                    $valueResource->getResourceName()
                );
                $valueObject['@id'] = $valueResourceAdapter->getApiUrl($valueResource);
                $valueObject['value_resource_id'] = $valueResource->getId();
                break;

            case Value::TYPE_URI:
                $valueObject['@id'] = $value->getValue();
                break;

            case Value::TYPE_LITERAL:
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
    public function getType()
    {
        return $this->getData()->getType();
    }

    /**
     * Get the value itself.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->getData()->getValue();
    }

    /**
     * Get the value language.
     *
     * @return string
     */
    public function getLang()
    {
        return $this->getData()->getLang();
    }

    /**
     * Get the value HTML flag.
     *
     * @return string
     */
    public function getIsHtml()
    {
        return $this->getData()->getIsHtml();
    }

    /**
     * Get the value resource.
     *
     * @return string
     */
    public function getValueResource()
    {
        $valueResource = $this->getData()->getValueResource();
        $valueResourceAdapter = $this->getAdapter(
            $valueResource->getResourceName()
        );
        return $valueResourceAdapter->getRepresentation(
            $valueResource->getId(),
            $valueResource
        );
    }
}
