<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Representation\DataTypeRepresentation;
use Omeka\Api\Resource;
use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * Data type adapter.
 */
class DataTypeAdapter extends AbstractAdapter
{
    public function getResourceName()
    {
        return 'data_types';
    }

    public function getRepresentationClass()
    {
        return DataTypeRepresentation::class;
    }

    public function search(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $resources = [];
        foreach ($manager->getRegisteredNames() as $resourceId) {
            $resources[] = new Resource($resourceId);
        }
        return new Response($resources);
    }

    public function read(Request $request)
    {
        return new Response(new Resource($request->getId()));
    }
}
