<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * Module adapter.
 */
class ModuleAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'modules';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\ModuleRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $response = new Response;
        $representations = [];
        foreach ($manager->getModules() as $id => $module) {
            $representations[$id] = $this->getRepresentation($module);
        }
        $response->setContent($representations);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $response = new Response;
        $representation = $this->getRepresentation($manager->getModule($request->getId()));
        $response->setContent($representation);
        return $response;
    }
}
