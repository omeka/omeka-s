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
        return array(
            'o:label' => $this->label(),
            'o:owner' => $this->getReference(
                null,
                $this->getData()->getOwner(),
                $this->getAdapter('users')
            ),
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
}
