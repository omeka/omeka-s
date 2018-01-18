<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\ResourceTemplateProperty;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
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
            'o:is_required' => $this->isRequired(),
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
     * @return string|null
     */
    public function dataType()
    {
        // Check the data type against the list of registered data types.
        $dataType = $this->templateProperty->getDataType();
        try {
            $this->getServiceLocator()->get('Omeka\DataTypeManager')->get($dataType);
        } catch (ServiceNotFoundException $e) {
            // Treat an unknown data type as "Default"
            $dataType = null;
        }
        return $dataType;
    }

    /**
     * @return string
     */
    public function dataTypeLabel()
    {
        $dataType = $this->dataType();
        if ($dataType === null) {
            return $this->getTranslator()->translate('Default');
        }
        return $this->getServiceLocator()->get('Omeka\DataTypeManager')
            ->get($dataType)->getLabel();
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->templateProperty->isRequired();
    }
}
