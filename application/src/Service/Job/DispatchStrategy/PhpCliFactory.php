<?php
namespace Omeka\Service\Job\DispatchStrategy;

use Omeka\Job\DispatchStrategy\PhpCli;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PhpCliFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @return PhpCli
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $viewHelpers = $services->get('ViewHelperManager');
        $basePathHelper = $viewHelpers->get('BasePath');
        $serverUrlHelper = $viewHelpers->get('ServerUrl');
        $config = $services->get('Config');
        $phpPath = null;
        if (isset($config['cli']['phpcli_path']) && $config['cli']['phpcli_path']) {
            $phpPath = $config['cli']['phpcli_path'];
        }
        return new PhpCli($services->get('Omeka\Cli'), $basePathHelper(), $serverUrlHelper(), $phpPath);
    }
}
