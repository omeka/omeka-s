<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\Value;
use Zend\ServiceManager\ServiceLocatorInterface;
use Omeka\Event\Event;

class ValueRepresentation extends AbstractRepresentation
{
    /**
     * @var Value
     */
    protected $value;

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
    }

    /**
     * Return this value as an unescaped string.
     *
     * @return string
     */
    public function __toString()
    {
        if (Value::TYPE_RESOURCE === $this->type()) {
            return $this->valueResource()->url(null, true);
        } else {
            return $this->value->getValue();
        }
    }

    /**
     * Return this value for display on a webpage.
     *
     * @return string
     */
    public function asHtml()
    {
        $args = [];
        switch ($this->type()) {
            case Value::TYPE_RESOURCE:
                $valueResource = $this->valueResource();
                $args['targetUrl'] = $valueResource->url();
                $args['targetId'] = $valueResource->id();
                $args['label'] = $valueResource->displayTitle();
                $html = $valueResource->link($valueResource->displayTitle());
                break;
            case Value::TYPE_URI:
                $uri = $this->value->getValue();
                $uriLabel = $this->value->getUriLabel();
                $args['targetUrl'] = $uri;
                if (!$uriLabel) {
                    $uriLabel = $uri;
                }
                $args['label'] = $uriLabel;
                $hyperlink = $this->getViewHelper('hyperlink');
                $html = $hyperlink($uriLabel, $uri);
                break;
            case Value::TYPE_LITERAL:
            default:
                $escape = $this->getViewHelper('escapeHtml');
                $html = nl2br($escape($this->value()));
        }
        $args['html'] = $html;
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs($args);
        $eventManager->trigger(Event::REP_VALUE_HTML, $this, $args);
        return $args['html'];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $value = $this->value;
        $valueObject = [];

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
        $resource = $this->value->getResource();
        return $this->getAdapter($resource->getResourceName())
            ->getRepresentation($resource);
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
            ->getRepresentation($this->value->getProperty());
    }

    /**
     * Get the value type.
     *
     * @return string
     */
    public function type()
    {
        return $this->value->getType();
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
     * Get the URI label.
     *
     * @return string
     */
    public function uriLabel()
    {
        return $this->value->getUriLabel();
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
        $resource = $this->value->getValueResource();
        if (!$resource) {
            return null;
        }
        $resourceAdapter = $this->getAdapter($resource->getResourceName());
        return $resourceAdapter->getRepresentation($resource);
    }
}
