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
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'api_resources';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\ApiResourceRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
        $resources = [];
        foreach ($manager->getRegisteredNames() as $resourceId) {
            $resources[] = new Resource($resourceId);
        }
        return new Response($resources);
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
        return new Response(new Resource($request->getId()));
    }
}
