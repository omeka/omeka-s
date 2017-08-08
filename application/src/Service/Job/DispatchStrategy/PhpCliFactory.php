<?php
namespace Omeka\Service\Job\DispatchStrategy;

use Omeka\Job\DispatchStrategy\PhpCli;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PhpCliFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @return PhpCliStrategy
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $basePathHelper = $services->get('ViewHelperManager')->get('BasePath');
        $config = $services->get('Config');
        $phpPath = null;
        if (isset($config['cli']['phpcli_path']) && $config['cli']['phpcli_path']) {
            $phpPath = $config['cli']['phpcli_path'];
        }
        return new PhpCli($services->get('Omeka\Cli'), $basePathHelper(), $phpPath);
    }
}
