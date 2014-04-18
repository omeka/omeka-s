<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Request;
use Omeka\Api\Response;

/**
 * Module adapter.
 */
class ModuleAdapter extends AbstractAdapter
{
    public function search(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $response = new Response;
        $response->setContent($manager->getModules());
        return $response;
    }

    public function read(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $response = new Response;
        $response->setContent($manager->getModule($request->getId()));
        return $response;
    }
}
