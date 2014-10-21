<?php
namespace Omeka\Api\Representation\Entity;

class VocabularyRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'vocabulary';
    }

    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            'o:namespace_uri' => $entity->getNamespaceUri(),
            'o:prefix'        => $entity->getPrefix(),
            'o:label'         => $entity->getLabel(),
            'o:comment'       => $entity->getComment(),
            'o:owner'         => $this->getReference(
                null,
                $entity->getOwner(),
                $this->getAdapter('users')
            ),
        );
    }

    public function label()
    {
        return $this->getData()->getLabel();
    }
    
    public function comment()
    {
        return $this->getData()->getComment();
    }
    
    public function properties()
    {
        $properties = array();
        $propertyAdapter = $this->getAdapter('properties');
        foreach ($this->getData()->getProperties() as $propertyEntity) {
            $properties[] = $propertyAdapter->getRepresentation(
                null, $propertyEntity
            );
        }
        return $properties;
    }
    
     public function resourceClasses()
     {
        $resourceClasses = array();
        $resourceClassAdapter = $this->getAdapter('resource_classes');
        foreach ($this->getData()->getResourceClasses() as $resourceClass) {
            $resourceClasses[] = $resourceClassAdapter->getRepresentation(
                null, $resourceClass
            );
        }
        return $resourceClasses;
     }
}
