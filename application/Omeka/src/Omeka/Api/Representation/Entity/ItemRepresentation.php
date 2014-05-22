<?php
namespace Omeka\Api\Representation\Entity;

class ItemRepresentation extends AbstractResourceEntityRepresentation
{
    public function jsonSerializeResource()
    {
        $item = $this->getData();
        return array(
            '@id'   => $this->getAdapter()->getApiUrl($item),
            'id'    => $item->getId(),
            'owner' => $this->getReference(
                null, $item->getOwner(), $this->getAdapter('users')
            ),
            'resource_class' => $this->getReference(
                null, $item->getResourceClass(), $this->getAdapter('resource_classes')
            ),
        );
    }
}
