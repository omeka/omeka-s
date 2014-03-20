<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Response;

/**
 * Module adapter.
 */
class ModuleAdapter extends AbstractAdapter
{
    public function search($data = null)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $response = new Response;
        $response->setContent($manager->getModules());
        return $response;
    }

    public function read($id, $data = null)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $response = new Response;
        $response->setContent($manager->getModule($id));
        return $response;
    }
}
