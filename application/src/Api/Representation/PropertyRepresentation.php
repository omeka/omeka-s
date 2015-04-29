<?php
namespace Omeka\Api\Representation;

class PropertyRepresentation extends AbstractVocabularyMemberRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'property';
    }

    /**
     * Get the resource count of this property.
     *
     * @return int
     */
    public function itemCount()
    {
        return $this->getAdapter()->getResourceCount(
            $this->getData(), null, 'Omeka\Entity\Item'
        );
    }
}
