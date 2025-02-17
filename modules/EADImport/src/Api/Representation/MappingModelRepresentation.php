<?php
namespace EADImport\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class MappingModelRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'mappingmodel';
    }

    public function getJsonLd()
    {
        return [
            'name' => $this->name(),
            'mapping' => $this->mapping(),
            'created' => $this->created(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:EADimportMappingModel';
    }

    public function name()
    {
        return $this->resource->getName();
    }

    public function mapping()
    {
        return $this->resource->getMapping();
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/eadimport/mapping-model-id',
            [
                'controller' => $this->getControllerName(),
                'action' => $action,
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }
}
