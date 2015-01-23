<?php
namespace Omeka\Api\Representation\Entity;

class ResourceClassRepresentation extends AbstractVocabularyMemberRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'resource-class';
    }
}
