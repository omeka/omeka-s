<?php
namespace Omeka\Api\Representation;

class AnnotationRepresentation extends AbstractResourceEntityRepresentation
{
    public function getResourceJsonLdType()
    {
        return 'o:Annotation';
    }

    public function getResourceJsonLd()
    {
        return [];
    }
}
