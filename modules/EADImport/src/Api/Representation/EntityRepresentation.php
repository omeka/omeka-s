<?php
namespace EADImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class EntityRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return [
            'o:job' => $this->job()->getReference(),
            'o:site' => $this->site()->getReference(),
            'entity_id' => $this->entityId(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:EADimportEntity';
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }

    public function site()
    {
        return $this->getAdapter('sites')
            ->getRepresentation($this->resource->getSite());
    }

    public function entityId()
    {
        return $this->resource->getEntityId();
    }

    public function resourceType()
    {
        return $this->resource->getResourceType();
    }
}
