<?php
namespace Omeka\Api\Representation;

use Omeka\DataType\Resource\AbstractResource;
use Omeka\Entity\Value;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ValueRepresentation extends AbstractRepresentation
{
    /**
     * @var Value
     */
    protected $value;

    /**
     * @var \Omeka\DataType\DataTypeInterface
     */
    protected $dataType;

    /**
     * Construct the value representation object.
     *
     * @param Value $value
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(Value $value, ServiceLocatorInterface $serviceLocator)
    {
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->value = $value;
        $this->dataType = $serviceLocator->get('Omeka\DataTypeManager')->getForExtract($value);
    }

    /**
     * Return this value as an unescaped string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->dataType->toString($this);
    }

    /**
     * Return this value for display on a webpage.
     *
     * @return string
     */
    public function asHtml($lang = null)
    {
        $view = $this->getServiceLocator()->get('ViewRenderer');
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs([
            'html' => $this->dataType->render($view, $this, $lang),
        ]);
        $eventManager->trigger('rep.value.html', $this, $args);
        return $args['html'];
    }

    public function jsonSerialize()
    {
        $valueObject = [
            'type' => $this->type(),
            'property_id' => $this->value->getProperty()->getId(),
            'property_label' => $this->value->getProperty()->getLabel(),
            'is_public' => $this->isPublic(),
        ];
        $jsonLd = $this->dataType->getJsonLd($this);
        if (!is_array($jsonLd)) {
            $jsonLd = [];
        }
        return $valueObject + $jsonLd;
    }

    /**
     * Get the resource representation.
     *
     * This is the subject of the RDF triple represented by this value.
     *
     * @return AbstractResourceEntityRepresentation
     */
    public function resource()
    {
        $resource = $this->value->getResource();
        return $this->getAdapter($resource->getResourceName())
            ->getRepresentation($resource);
    }

    /**
     * Get the property representation.
     *
     * This is the predicate of the RDF triple represented by this value.
     *
     * @return PropertyRepresentation
     */
    public function property()
    {
        return $this->getAdapter('properties')
            ->getRepresentation($this->value->getProperty());
    }

    /**
     * Get the value type.
     *
     * @return string
     */
    public function type()
    {
        // The data type resolved by the data type manager takes precedence over
        // the one stored in the database.
        return $this->dataType->getName();
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
        return $this->value->getValue();
    }

    /**
     * Get the value language.
     *
     * @return string
     */
    public function lang()
    {
        return $this->value->getLang();
    }

    /**
     * Get the URI.
     *
     * @return string
     */
    public function uri()
    {
        return $this->value->getUri();
    }

    /**
     * Get the value resource representation.
     *
     * This is the object of the RDF triple represented by this value.
     *
     * @return AbstractResourceEntityRepresentation|null
     */
    public function valueResource()
    {
        $resource = $this->value->getValueResource();
        if (!$resource) {
            return null;
        }
        $resourceAdapter = $this->getAdapter($resource->getResourceName());
        return $resourceAdapter->getRepresentation($resource);
    }

    /**
     * Get whether this value is public.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->value->isPublic();
    }

    /**
     * Return whether this value should be hidden when listing values for its
     * parent resource.
     *
     * Currently the only "hidden" values are resource-type values that point
     * to unretrievable resources (i.e., private resources the current user
     * cannot see).
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->dataType instanceof AbstractResource && null === $this->value->getValueResource();
    }
}
