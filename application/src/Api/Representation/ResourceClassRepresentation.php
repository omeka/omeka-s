<?php
namespace Omeka\Api\Representation;

class ResourceClassRepresentation extends AbstractVocabularyMemberRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'resource-class';
    }

    /**
     * Get the resource count of this resource class.
     *
     * @return int
     */
    public function itemCount()
    {
        return $this->getAdapter()->getResourceCount(
            $this->getData(), 'resourceClass', 'Omeka\Entity\Item'
        );
    }
}
