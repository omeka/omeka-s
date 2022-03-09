<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DataType;
use Laminas\EventManager\Event;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the dataType view helper.
 */
class DataTypeFactory implements FactoryInterface
{
    /**
     * Create and return the dataType view helper
     *
     * @return DataType
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $eventManager = $services->get('EventManager');
        $args = $eventManager->prepareArgs(['data_types' => $config['data_types']['value_annotating']]);
        $eventManager->triggerEvent(new Event('data_types.value_annotating', null, $args));
        return new DataType(
            $services->get('Omeka\DataTypeManager'),
            $args['data_types']
        );
    }
}
