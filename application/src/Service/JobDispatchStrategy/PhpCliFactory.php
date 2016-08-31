<?php
namespace Omeka\Service\JobDispatchStrategy;

use Omeka\Job\Strategy\PhpCliStrategy;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PhpCliFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @return PhpCliStrategy
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $cli = $serviceLocator->get('Omeka\Cli');
        $basePathHelper = $serviceLocator->get('ViewHelperManager')->get('BasePath');
        $config = $serviceLocator->get('Config');
        $phpPath = null;
        if (isset($config['cli']['phpcli_path']) && $config['cli']['phpcli_path']) {
            $phpPath = $config['cli']['phpcli_path'];
        }
        return new PhpCliStrategy($cli, $basePathHelper(), $phpPath);
    }
}
