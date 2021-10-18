<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\DataType;
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
        return new DataType(
            $services->get('Omeka\DataTypeManager'),
            $config['data_types']['value_annotating'],
        );
    }
}
