<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * Module adapter.
 */
class ModuleAdapter extends AbstractAdapter
{
    public function getResourceName()
    {
        return 'modules';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\ModuleRepresentation::class;
    }

    public function search(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        return new Response($manager->getModules());
    }

    public function read(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        return new Response($manager->getModule($request->getId()));
    }
}
