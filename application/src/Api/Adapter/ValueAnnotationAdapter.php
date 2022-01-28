<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Representation\ValueAnnotationRepresentation;
use Omeka\Entity\ValueAnnotation;

class ValueAnnotationAdapter extends AbstractResourceEntityAdapter
{
    public function getResourceName()
    {
        return 'value_annotations';
    }

    public function getRepresentationClass()
    {
        return ValueAnnotationRepresentation::class;
    }

    public function getEntityClass()
    {
        return ValueAnnotation::class;
    }
}
