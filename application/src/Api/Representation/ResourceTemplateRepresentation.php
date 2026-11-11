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
        $owner = $this->owner();
        $resourceClass = $this->resourceClass();
        $titleProperty = $this->titleProperty();
        $descriptionProperty = $this->descriptionProperty();

        return [
            'o:label' => $this->label(),
            'o:owner' => $owner ? $owner->getReference()->jsonSerialize() : null,
            'o:resource_class' => $resourceClass ? $resourceClass->getReference()->jsonSerialize() : null,
            'o:title_property' => $titleProperty ? $titleProperty->getReference()->jsonSerialize() : null,
            'o:description_property' => $descriptionProperty ? $descriptionProperty->getReference()->jsonSerialize() : null,
            'o:resource_template_property' => array_map(fn ($v) => $v->jsonSerialize(), $this->resourceTemplateProperties()),
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
     * @mixed ResourceTemplatePropertyRepresentation
     */
    public function resourceTemplateProperty($propertyId)
    {
        $resTemProp = $this->resource->getResourceTemplateProperties()->get($propertyId);
        if ($resTemProp) {
            return new ResourceTemplatePropertyRepresentation($resTemProp, $this->getServiceLocator());
        }
        return null;
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
