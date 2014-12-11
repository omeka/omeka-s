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
}
