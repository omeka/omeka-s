<?php
namespace Omeka\Api\Representation\Entity;

class ItemRepresentation extends AbstractResourceEntity
{
    public function extract()
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        $entity = $this->getData();
        $item = array(
            '@id'   => $this->getAdapter()->getApiUrl($entity),
            'id'    => $entity->getId(),
            'owner' => $this->getReference(
                null, $entity->getOwner(), $this->getAdapter('users')
            ),
            'resource_class' => $this->getReference(
                null, $entity->getResourceClass(), $this->getAdapter('resource_classes')
            ),
        );
        return $this->getRepresentation($entity, $item);
    }
}
