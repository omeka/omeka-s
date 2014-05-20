<?php
namespace Omeka\Api\Representation\Entity;

class PropertyRepresentation extends AbstractResourceEntity
{
    public function extract()
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        $entity = $this->getData();
        return array(
            '@id'        => $this->getAdapter()->getApiUrl($entity),
            'id'         => $entity->getId(),
            'local_name' => $entity->getLocalName(),
            'label'      => $entity->getLabel(),
            'comment'    => $entity->getComment(),
            //~ 'vocabulary' => $this->getReference(
                //~ $entity->getVocabulary()->getId(),
                //~ $entity->getVocabulary(),
                //~ $this->getAdapter('vocabularies')
            //~ ),
            //~ 'owner'      => $this->getReference(
                //~ $entity->getOwner()->getId(),
                //~ $entity->getOwner(),
                //~ $this->getAdapter('users')
            //~ ),
        );
    }
}
