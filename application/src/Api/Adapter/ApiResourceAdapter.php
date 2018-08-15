<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Resource;
use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * API resource adapter.
 */
class ApiResourceAdapter extends AbstractAdapter
{
    public function getResourceName()
    {
        return 'api_resources';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\ApiResourceRepresentation::class;
    }

    public function search(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
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
