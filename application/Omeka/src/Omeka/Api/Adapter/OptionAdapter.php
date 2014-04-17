<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Response;

/**
 * Option adapter.
 */
class OptionAdapter extends AbstractAdapter
{
    public function read(Request $request)
    {
        $options = $this->getServiceLocator()->get('Omeka\Options');
        $response = new Response;
        $response->setContent($manager->get($request->getId()));
        return $response;
    }
}
