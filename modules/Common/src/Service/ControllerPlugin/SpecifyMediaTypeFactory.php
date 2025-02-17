<?php declare(strict_types=1);

namespace Common\Service\ControllerPlugin;

use Common\Mvc\Controller\Plugin\SpecifyMediaType;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SpecifyMediaTypeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $mediaTypesIdentifiers = require dirname(__DIR__, 3) . '/data/media-types/media-type-identifiers.php';
        return new SpecifyMediaType(
            $services->get('Omeka\Logger'),
            $services->get('Omeka\Connection'),
            $config['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files'),
            $mediaTypesIdentifiers
        );
    }
}
