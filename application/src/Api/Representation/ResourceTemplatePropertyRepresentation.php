<?php
namespace Omeka\Api\Representation;

use Omeka\Entity\ResourceTemplateProperty;
use Laminas\ServiceManager\ServiceLocatorInterface;

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

    public function jsonSerialize()
    {
        return [
            'o:property' => $this->property()->getReference(),
            'o:alternate_label' => $this->alternateLabel(),
            'o:alternate_comment' => $this->alternateComment(),
            'o:data_type' => $this->dataTypes(),
            'o:is_required' => $this->isRequired(),
            'o:is_private' => $this->isPrivate(),
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
     * @deprecated Since version 3.0.0. Use dataTypes() instead.
     * @return string|null
     */
    public function dataType()
    {
        // Check the data type against the list of registered data types.
        $dataTypes = $this->templateProperty->getDataType();
        if (empty($dataTypes)) {
            return null;
        }
        $dataType = reset($dataTypes);
        // Treat an unknown data type as "Default".
        return $this->getServiceLocator()->get('Omeka\DataTypeManager')->has($dataType)
            ? $dataType
            : null;
    }

    /**
     * @return string[]
     */
    public function dataTypes()
    {
        // Check the data type against the list of registered data types.
        $dataTypes = $this->templateProperty->getDataType();
        if (empty($dataTypes)) {
            return [];
        }
        $dataTypeManager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $result = [];
        foreach ($dataTypes as $dataType) {
            // Treat an unknown data type as "Default".
            if ($dataTypeManager->has($dataType)) {
                $result[] = $dataType;
            }
        }
        return $result;
    }

    /**
     * @deprecated Since version 3.0.0. Use dataTypeLabels() instead.
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
     * @return array List of data type names and labels.
     */
    public function dataTypeLabels()
    {
        $result = [];
        $dataTypeManager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        foreach ($this->dataTypes() as $dataType) {
            $result[] = [
                'name' => $dataType,
                'label' => $dataTypeManager->get($dataType)->getLabel(),
            ];
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->templateProperty->isRequired();
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->templateProperty->isPrivate();
    }
}
