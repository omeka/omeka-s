<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Representation\ValueAnnotationRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Api\Request;
use Omeka\Api\ResourceInterface;
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

    /**
     * Compose a resource representation object.
     *
     * The value is not directly available in ValueAnnotation data, because
     * their one-to-one relation is unidirectional, so append it here.
     *
     * @param mixed $data Whatever data is needed to compose the representation.
     * @param ValueRepresentation|null
     * @return ResourceInterface|null
     */
    public function getRepresentation(ResourceInterface $data = null, ?ValueRepresentation $value = null)
    {
        if (!$data instanceof ValueAnnotation || !$value) {
            // Do not attempt to compose a null representation.
            return null;
        }
        return new ValueAnnotationRepresentation($data, $this, $value);
    }
}
