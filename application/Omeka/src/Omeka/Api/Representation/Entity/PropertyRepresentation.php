<?php
namespace Omeka\Api\Representation\Entity;

class PropertyRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        $entity = $this->getData();
        return array(
            'o:local_name' => $entity->getLocalName(),
            'o:label'      => $entity->getLabel(),
            'o:comment'    => $entity->getComment(),
            'o:term'       => $entity->getVocabulary()->getPrefix() . ':' . $entity->getLocalName(),
            'o:vocabulary' => $this->getReference(
                null,
                $entity->getVocabulary(),
                $this->getAdapter('vocabularies')
            ),
            'o:owner' => $this->getReference(
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
