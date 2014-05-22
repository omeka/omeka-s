<?php
namespace Omeka\Api\Representation\Entity;

class ResourceClassRepresentation extends AbstractEntityRepresentation
{
    public function jsonSerialize()
    {
        $entity = $this->getData();
        return array(
            '@id'        => $this->getAdapter()->getApiUrl($entity),
            'id'         => $entity->getId(),
            'local_name' => $entity->getLocalName(),
            'label'      => $entity->getLabel(),
            'comment'    => $entity->getComment(),
            'vocabulary' => $this->getReference(
                null, $entity->getVocabulary(), $this->getAdapter('vocabularies')
            ),
            'owner'      => $this->getReference(
                null, $entity->getOwner(), $this->getAdapter('users')
            ),
        );
    }
}
