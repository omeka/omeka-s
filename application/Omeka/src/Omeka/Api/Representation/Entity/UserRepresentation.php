<?php
namespace Omeka\Api\Representation\Entity;

class UserRepresentation extends AbstractEntityRepresentation
{
    public function jsonSerialize()
    {
        $entity = $this->getData();
        return array(
            '@id'      => $this->getAdapter()->getApiUrl($entity),
            'id'       => $entity->getId(),
            'username' => $entity->getUsername(),
            'name'     => $entity->getName(),
            'email'    => $entity->getEmail(),
            'created'  => $this->getDateTime($entity->getCreated()),
            'role'     => $entity->getRole(),
        );
    }
}
