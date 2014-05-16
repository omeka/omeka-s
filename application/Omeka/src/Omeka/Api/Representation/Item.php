<?php
namespace Omeka\Api\Representation;

class Item extends AbstractResourceEntity
{
    public function toArray()
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        $entity = $this->getData();
        $adapter = $this->getAdapter($this->getResourceName());
        $item = array(
            '@id'   => $adapter->getApiUrl($entity),
            'id'    => $entity->getId(),
            'owner' => $adapter->getReference('users', $entity->getOwner()),
            'resource_class' => $adapter->getReference(
                'resource_classes', $entity->getResourceClass()
            ),
        );
        return $this->getRepresentation($entity, $item);
    }
}
