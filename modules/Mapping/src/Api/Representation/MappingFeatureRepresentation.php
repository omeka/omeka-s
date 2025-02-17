<?php
namespace Mapping\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class MappingFeatureRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-mapping:Feature';
    }

    public function getJsonLd()
    {
        $media = $this->media();
        return [
            'o:item' => $this->item()->getReference(),
            'o:media' => $media ? $media->getReference() : null,
            'o:label' => $this->label(),
            'o-module-mapping:geography-type' => $this->geographyType(),
            'o-module-mapping:geography-coordinates' => $this->geographyCoordinates(),
        ];
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function media()
    {
        // The media may not be public, so fetch the media directly from the
        // entity manager to leverage the resource visibility filter. Otherwise,
        // an EntityNotFound exception will be raised when attempting to fetch
        // data from the Doctrine proxy returned from getMedia().
        $media = $this->getServiceLocator()
            ->get('Omeka\EntityManager')
            ->getRepository('Omeka\Entity\Media')
            ->findOneBy(['id' => $this->resource->getMedia()]);
        return $this->getAdapter('media')->getRepresentation($media);
    }

    public function label()
    {
        return $this->resource->getLabel();
    }

    public function geography()
    {
        return $this->resource->getGeography();
    }

    public function geographyType()
    {
        return $this->geography()->getType();
    }

    public function geographyCoordinates()
    {
        return $this->geography()->toArray();
    }
}
