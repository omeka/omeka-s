<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Representation\AnnotationRepresentation;
use Omeka\Entity\Annotation;

class AnnotationAdapter extends AbstractResourceEntityAdapter
{
    public function getResourceName()
    {
        return 'annotations';
    }

    public function getRepresentationClass()
    {
        return AnnotationRepresentation::class;
    }

    public function getEntityClass()
    {
        return Annotation::class;
    }
}
