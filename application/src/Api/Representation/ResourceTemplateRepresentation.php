<?php
namespace Omeka\Api\Representation;

class ResourceTemplateRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'resource-template';
    }

    public function getJsonLdType()
    {
        return 'o:ResourceTemplate';
    }

    public function getJsonLd()
    {
        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }
        $resourceClass = null;
        if ($this->resourceClass()) {
            $resourceClass = $this->resourceClass()->getReference();
        }
        $titleProperty = null;
        if ($this->titleProperty()) {
            $titleProperty = $this->titleProperty()->getReference();
        }
        $descriptionProperty = null;
        if ($this->descriptionProperty()) {
            $descriptionProperty = $this->descriptionProperty()->getReference();
        }
        return [
            'o:label' => $this->label(),
            'o:owner' => $owner,
            'o:resource_class' => $resourceClass,
            'o:title_property' => $titleProperty,
            'o:description_property' => $descriptionProperty,
            'o:settings' => $this->settings(),
            'o:resource_template_property' => $this->resourceTemplateProperties(),
        ];
    }

    /**
     * Return the resource template label.
     *
     * @return string
     */
    public function label()
    {
        return $this->resource->getLabel();
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    /**
     * Return the resource class assigned to this resource template.
     *
     * @return ResourceClassRepresentation
     */
    public function resourceClass()
    {
        return $this->getAdapter('resource_classes')
            ->getRepresentation($this->resource->getResourceClass());
    }

    /**
     * Return the title property of this resource template.
     *
     * @return PropertyRepresentation
     */
    public function titleProperty()
    {
        return $this->getAdapter('properties')
            ->getRepresentation($this->resource->getTitleProperty());
    }

    /**
     * Return the description property of this resource template.
     *
     * @return PropertyRepresentation
     */
    public function descriptionProperty()
    {
        return $this->getAdapter('properties')
            ->getRepresentation($this->resource->getDescriptionProperty());
    }

    /**
     * @return array
     */
    public function settings()
    {
        return $this->resource->getSettings();
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function setting($name, $default = null)
    {
        $settings = $this->resource->getSettings();
        return array_key_exists($name, $settings)
            ? $settings[$name]
            : $default;
    }

    /**
     * Return the properties assigned to this resource template.
     *
     * @return ResourceTemplatePropertyRepresentation[]
     */
    public function resourceTemplateProperties()
    {
        $resTemProps = [];
        foreach ($this->resource->getResourceTemplateProperties() as $resTemProp) {
            $resTemProps[] = new ResourceTemplatePropertyRepresentation(
                $resTemProp, $this->getServiceLocator());
        }
        return $resTemProps;
    }

    /**
     * Return the specified template property or null if it doesn't exist.
     *
     * @param int $propertyId
     * @param string $dataType
     * @param bool $all
     * @mixed ResourceTemplatePropertyRepresentation|ResourceTemplatePropertyRepresentation[]|null
     */
    public function resourceTemplateProperty($propertyId, $dataType = null, $all = false)
    {
        $propertyId = (int) $propertyId;
        $resTemProps = $this->resource->getResourceTemplateProperties()
            ->filter(function (\Omeka\Entity\ResourceTemplateProperty $resTemProp) use ($propertyId, $dataType, $all) {
                if ($resTemProp->getProperty()->getId() !== $propertyId) {
                    return false;
                }
                if (empty($dataType)) {
                    return true;
                }
                $dataTypes = $resTemProp->getDataType();
                return in_array($dataType, $dataTypes);
            });
        if (!count($resTemProps)) {
            return $all ? [] : null;
        }

        $services = $this->getServiceLocator();
        if ($all) {
            return array_map(function ($resTemProp) use ($services) {
                return new ResourceTemplatePropertyRepresentation($resTemProp, $services);
            }, $resTemProps);
        } else {
            // Return the template property without data type, if any.
            if (empty($dataType) && count($resTemProps) > 1) {
                foreach ($resTemProps as $resTemProp) {
                    if (!$resTemProp->getDataType()) {
                        return new ResourceTemplatePropertyRepresentation($resTemProp, $services);
                    }
                }
            }
            return new ResourceTemplatePropertyRepresentation($resTemProps->first(), $services);
        }
    }

    /**
     * Get the display resource class label for this resource template.
     *
     * @param string|null $default
     * @return string|null
     */
    public function displayResourceClassLabel($default = null)
    {
        $resourceClass = $this->resourceClass();
        return $resourceClass ? $resourceClass->label() : $default;
    }

    /**
     * Get the item count of this resource template.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', [
                'resource_template_id' => $this->id(),
                'limit' => 0,
            ]);
        return $response->getTotalResults();
    }
}
