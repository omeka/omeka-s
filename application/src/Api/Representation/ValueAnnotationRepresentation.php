<?php
namespace Omeka\Api\Representation;

class ValueAnnotationRepresentation extends AbstractResourceEntityRepresentation
{
    public function getResourceJsonLdType()
    {
        return 'o:ValueAnnotation';
    }

    public function getResourceJsonLd()
    {
        return [];
    }
}
