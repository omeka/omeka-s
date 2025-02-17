<?php declare(strict_types=1);

namespace Common\Service\ViewHelper;

use Common\View\Helper\DataType;
use Interop\Container\ContainerInterface;
use Laminas\EventManager\Event;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory for the dataType view helper.
 *
 * Override the core view helper in order to use the form element DataTypeSelect.
 *
 * @see \Omeka\Service\ViewHelper\DataTypeFactory
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
            $args['data_types'],
            $services->get('FormElementManager')
        );
    }
}
