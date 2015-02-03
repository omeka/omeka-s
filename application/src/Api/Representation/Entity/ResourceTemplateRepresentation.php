<?php
namespace Omeka\Api\Representation\Entity;

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
    public function getJsonLd()
    {
        $resTemProps = array();
        foreach ($this->getData()->getResourceTemplateProperties() as $resTemProp) {
            $resTemProps[] = array(
                'o:property' => $this->getReference(
                    null,
                    $resTemProp->getProperty(),
                    $this->getAdapter('properties')
                ),
                'o:alternate_label' => $resTemProp->getAlternateLabel(),
                'o:alternate_comment' => $resTemProp->getAlternateComment(),
            );
        }

        return array(
            'o:label' => $this->label(),
            'o:owner' => $this->getReference(
                null,
                $this->getData()->getOwner(),
                $this->getAdapter('users')
            ),
            'o:resource_class' => $this->getReference(
                null,
                $this->getData()->getResourceClass(),
                $this->getAdapter('resource_classes')
            ),
            'o:resource_template_property' => $resTemProps,
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
     * Since resource template properties are not API resources, this cannot
     * return an array of representations. Instead this returns the array of
     * resource template properties as built by {@link self::getJsonLd()}.
     *
     * @return array
     */
    public function resourceTemplateProperties()
    {
        $jsonLd = $this->getJsonLd();
        return $jsonLd['o:resource_template_property'];
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
        return $this->getAdapter()->getResourceCount(
            $this->getData(), 'resourceTemplate', 'Omeka\Model\Entity\Item'
        );
    }
}
