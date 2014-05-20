<?php
namespace Omeka\Api\Representation\Entity;

class VocabularyRepresentation extends AbstractResourceEntity
{
    public function extract()
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        $entity = $this->getData();
        return array(
            '@id'           => $this->getAdapter()->getApiUrl($entity),
            'id'            => $entity->getId(),
            'namespace_uri' => $entity->getNamespaceUri(),
            'prefix'        => $entity->getPrefix(),
            'label'         => $entity->getLabel(),
            'comment'       => $entity->getComment(),
            'owner'      => $this->getReference(
                null, $entity->getOwner(), $this->getAdapter('users')
            ),
        );
    }
}
