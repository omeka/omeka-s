<?php
namespace Omeka\Api\Representation;

class ResourceTemplateRepresentation extends AbstractEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'resource-template';
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:ResourceTemplate';
    }

    /**
     * {@inheritDoc}
     */
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
        return array(
            'o:label' => $this->label(),
            'o:owner' => $owner,
            'o:resource_class' => $resourceClass,
            'o:resource_template_property' => $this->resourceTemplateProperties(),
        );
    }

    /**
     * Return the resource template label.
     *
     * @return string
     */
    public function label()
    {
        return $this->getData()->getLabel();
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation(null, $this->getData()->getOwner());
    }

    /**
     * Return the resource class assigned to this resource template.
     *
     * @return ResourceClassRepresentation
     */
    public function resourceClass()
    {
        return $this->getAdapter('resource_classes')
            ->getRepresentation(null, $this->getData()->getResourceClass());
    }

    /**
     * Return the properties assigned to this resource template.
     *
     * @return array
     */
    public function resourceTemplateProperties()
    {
        $resTemProps = array();
        foreach ($this->getData()->getResourceTemplateProperties() as $resTemProp) {
            $resTemProps[]= new ResourceTemplatePropertyRepresentation(
                $resTemProp, $this->getServiceLocator());
        }
        return $resTemProps;
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
            ->search('items', array(
                'resource_template_id' => $this->id(),
                'limit' => 0,
            ));
        return $response->getTotalResults();
    }
}
