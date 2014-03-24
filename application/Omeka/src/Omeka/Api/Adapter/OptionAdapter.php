<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Response;

/**
 * Option adapter.
 */
class OptionAdapter extends AbstractAdapter
{
    public function read($id, $data = null)
    {
        $options = $this->getServiceLocator()->get('Omeka\Options');
        $response = new Response;
        $response->setContent($manager->get($id));
        return $response;
    }
}
