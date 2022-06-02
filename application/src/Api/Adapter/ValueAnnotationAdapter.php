<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Representation\ValueAnnotationRepresentation;
use Omeka\Api\Request;
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

    public function search(Request $request)
    {
        return AbstractAdapter::search($request);
    }

    public function read(Request $request)
    {
        return AbstractAdapter::read($request);
    }

    public function create(Request $request)
    {
        return AbstractAdapter::create($request);
    }

    public function update(Request $request)
    {
        return AbstractAdapter::update($request);
    }

    public function delete(Request $request)
    {
        return AbstractAdapter::delete($request);
    }
}
