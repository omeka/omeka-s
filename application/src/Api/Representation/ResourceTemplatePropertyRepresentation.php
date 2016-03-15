<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\ResourceTemplateProperty;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceTemplatePropertyRepresentation extends AbstractRepresentation
{
    /**
     * @var ResourceTemplateProperty
     */
    protected $templateProperty;

    /**
     * Construct the resource template property representation object.
     *
     * @param ResourceTemplateProperty $templateProperty
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ResourceTemplateProperty $templateProperty, ServiceLocatorInterface $serviceLocator)
    {
        // Set the service locator first.
        $this->setServiceLocator($serviceLocator);
        $this->templateProperty = $templateProperty;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'o:property' => $this->property()->getReference(),
            'o:alternate_label' => $this->alternateLabel(),
            'o:alternate_comment' => $this->alternateComment(),
            'o:data_type' => $this->dataType(),
        ];
    }

    /**
     * @return ResourceTemplateRepresentation
     */
    public function template()
    {
        return $this->getAdapter('resource_templates')
            ->getRepresentation($this->templateProperty->getResourceTemplate());
    }

    /**
     * @return PropertyRepresentation
     */
    public function property()
    {
        return $this->getAdapter('properties')
            ->getRepresentation($this->templateProperty->getProperty());
    }

    /**
     * @return string
     */
    public function alternateLabel()
    {
        return $this->templateProperty->getAlternateLabel();
    }

    /**
     * @return string
     */
    public function alternateComment()
    {
        return $this->templateProperty->getAlternateComment();
    }

    /**
     * @return int
     */
    public function position()
    {
        return $this->templateProperty->getPosition();
    }

    /**
     * @return string
     */
    public function dataType()
    {
        return $this->templateProperty->getDataType();
    }

    /**
     * @return string
     */
    public function dataTypeLabel()
    {
        return $this->getServiceLocator()->get('Omeka\DataTypeManager')
            ->get($this->dataType())->getLabel();
    }
}
