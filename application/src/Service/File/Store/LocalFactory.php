<?php
namespace Omeka\Service\File\Store;

use Omeka\File\Store\Local;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the Local file store.
 */
class LocalFactory implements FactoryInterface
{
    /**
     * Create and return the Local file store
     *
     * @return Local
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');

        $basePath = $config['file_store']['local']['base_path'];
        if (null === $basePath) {
            $basePath = OMEKA_PATH . '/files';
        }

        $baseUri = $config['file_store']['local']['base_uri'];
        if (null === $baseUri) {
            $helpers = $services->get('ViewHelperManager');
            $serverUrlHelper = $helpers->get('ServerUrl');
            $basePathHelper = $helpers->get('BasePath');
            $baseUri = $serverUrlHelper($basePathHelper('files'));
        }
        return new Local($basePath, $baseUri, $services->get('Omeka\Logger'));
    }
}
