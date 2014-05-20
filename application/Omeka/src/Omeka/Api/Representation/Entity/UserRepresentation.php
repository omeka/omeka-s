<?php
namespace Omeka\Api\Representation\Entity;

class UserRepresentation extends AbstractResourceEntity
{
    public function extract()
    {
        return $this->jsonSerialize();
    }

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
