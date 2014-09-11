<?php
namespace Omeka\Api\Representation\Entity;

class UserRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            '@id'            => $this->getAdapter()->getApiUrl($entity),
            'o:id'       => $entity->getId(),
            'o:username' => $entity->getUsername(),
            'o:name'     => $entity->getName(),
            'o:email'    => $entity->getEmail(),
            'o:created'  => $this->getDateTime($entity->getCreated()),
            'o:role'     => $entity->getRole(),
        );
    }

    public function getUsername()
    {
        return $this->getData()->getUsername();
    }

    public function getName()
    {
        return $this->getData()->getName();
    }

    public function getEmail()
    {
        return $this->getData()->getEmail();
    }

    public function getRole()
    {
        return $this->getData()->getRole();
    }

    public function getCreated()
    {
        return $this->getData()->getCreated();
    }

    public function getEntity()
    {
        return $this->getData();
    }
}
