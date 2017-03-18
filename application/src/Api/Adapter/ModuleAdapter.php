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
        $content = $this->prepareResponseContent($manager->getModules(), $request);
        return new Response($content);
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $content = $this->prepareResponseContent($manager->getModule($request->getId()), $request);
        return new Response($content);
    }
}
