<?php
namespace Omeka\Api\Representation\Entity;

class ItemRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLd()
    {
        $mediaReferences = array();
        foreach ($this->getData()->getMedia() as $media) {
            $mediaReferences[] =  $this->getReference(
                null, $media, $this->getAdapter('media')
            );
        }
        return array(
            'o:media' => $mediaReferences,
        );
    }
}
