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
}
