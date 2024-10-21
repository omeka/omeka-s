<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Representation\DataTypeRepresentation;
use Omeka\Api\Resource;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\DataType\ConvertableInterface;

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

        // Filter for is_convertable.
        $isConvertable = $request->getValue('is_convertable', null);
        if (null !== $isConvertable) {
            $resources = array_filter($resources, function ($resource) use ($isConvertable, $manager) {
                $dataType = $manager->get($resource->getId());
                if ($isConvertable) {
                    return ($dataType instanceof ConvertableInterface);
                } else {
                    return !($dataType instanceof ConvertableInterface);
                }
            });
        }
        return new Response(array_values($resources));
    }

    public function read(Request $request)
    {
        return new Response(new Resource($request->getId()));
    }
}
