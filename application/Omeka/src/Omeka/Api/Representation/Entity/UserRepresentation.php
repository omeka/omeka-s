<?php
namespace Omeka\Api\Representation\Entity;

class UserRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            'o:username' => $entity->getUsername(),
            'o:name'     => $entity->getName(),
            'o:email'    => $entity->getEmail(),
            'o:created'  => $this->getDateTime($entity->getCreated()),
            'o:role'     => $entity->getRole(),
        );
    }

    public function username()
    {
        return $this->getData()->getUsername();
    }

    public function name()
    {
        return $this->getData()->getName();
    }

    public function email()
    {
        return $this->getData()->getEmail();
    }

    public function role()
    {
        return $this->getData()->getRole();
    }

    public function created()
    {
        return $this->getData()->getCreated();
    }

    public function getEntity()
    {
        return $this->getData();
    }
}
