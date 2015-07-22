<?php
namespace Omeka\Api\Representation;

use Omeka\Api\Exception;
use Omeka\Entity\Value;
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
     * Return this value as an unescaped string.
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->type()) {
            case Value::TYPE_RESOURCE:
                return $this->valueResource()->url();
            case Value::TYPE_URI:
            case Value::TYPE_LITERAL:
            default:
                return $this->getData()->getValue();
        }
    }

    /**
     * Return this value for display on a webpage.
     *
     * @return string
     */
    public function displayValue()
    {
        switch ($this->type()) {
            case Value::TYPE_RESOURCE:
                $valueResource = $this->valueResource();
                return $valueResource->link($valueResource->displayTitle());
            case Value::TYPE_URI:
                $uri = $this->getData()->getValue();
                $uriLabel = $this->getData()->getUriLabel();
                if (!$uriLabel) {
                    $uriLabel = $uri;
                }
                $hyperlink = $this->getViewHelper('hyperlink');
                return $hyperlink($uriLabel, $uri);
            case Value::TYPE_LITERAL:
            default:
                $escape = $this->getViewHelper('escapeHtml');
                return $escape($this->getData()->getValue());
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

        switch ($this->type()) {

            case Value::TYPE_RESOURCE:
                $valueResource = $this->valueResource();
                $valueObject = $valueResource->valueRepresentation();
                break;

            case Value::TYPE_URI:
                $valueObject['@id'] = $value->getValue();
                if ($value->getUriLabel()) {
                    $valueObject['o:uri_label'] = $value->getUriLabel();
                }
                break;

            case Value::TYPE_LITERAL:
            default:
                $valueObject['@value'] = $value->getValue();
                if ($value->getLang()) {
                    $valueObject['@language'] = $value->getLang();
                }
                break;
        }

        $valueObject['property_id'] = $value->getProperty()->getId();
        $valueObject['property_label'] = $value->getProperty()->getLabel();

        return $valueObject;
    }

    /**
     * Get the resource representation.
     *
     * This is the subject of the RDF triple represented by this value.
     *
     * @return Entity\AbstractResourceEntityRepresentation
     */
    public function resource()
    {
        $resource = $this->getData()->getResource();
        return $this->getAdapter($resource->getResourceName())
            ->getRepresentation(null, $resource);
    }

    /**
     * Get the property representation.
     *
     * This is the predicate of the RDF triple represented by this value.
     *
     * @return Entity\PropertyRepresentation
     */
    public function property()
    {
        return $this->getAdapter('properties')
            ->getRepresentation(null, $this->getData()->getProperty());
    }

    /**
     * Get the value type.
     *
     * @return string
     */
    public function type()
    {
        return $this->getData()->getType();
    }

    /**
     * Get the value itself.
     *
     * This is the object of the RDF triple represented by this value.
     *
     * @return string
     */
    public function value()
    {
        return $this->getData()->getValue();
    }

    /**
     * Get the value language.
     *
     * @return string
     */
    public function lang()
    {
        return $this->getData()->getLang();
    }

    /**
     * Get the URI label.
     *
     * @return string
     */
    public function uriLabel()
    {
        return $this->getData()->getUriLabel();
    }

    /**
     * Get the value resource representation.
     *
     * This is the object of the RDF triple represented by this value.
     *
     * @return null|Entity\AbstractResourceEntityRepresentation
     */
    public function valueResource()
    {
        $resource = $this->getData()->getValueResource();
        if (!$resource) {
            return null;
        }
        $resourceAdapter = $this->getAdapter($resource->getResourceName());
        return $resourceAdapter->getRepresentation($resource->getId(), $resource);
    }
}
