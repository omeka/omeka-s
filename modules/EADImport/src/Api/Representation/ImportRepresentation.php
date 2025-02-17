<?php
namespace EADImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class ImportRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'import';
    }

    public function getJsonLd()
    {
        return [
            'o:job' => $this->job()->getReference(),
            'o:sites' => $this->site()->getReference(),
            'resource_type' => $this->resourceType(),
            'name' => $this->name(),
            'mapping' => $this->mapping(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:EADimportImport';
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

    public function resourceType()
    {
        return $this->resource->getResourceType();
    }

    public function name()
    {
        return $this->resource->getName();
    }

    public function mapping()
    {
        return $this->resource->getMapping();
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/eadimport/mapping',
            [
                'controller' => $this->getControllerName(),
                'action' => $action,
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }
}
