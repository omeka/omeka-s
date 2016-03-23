<?php
namespace Omeka\Service\JobDispatchStrategy;

use Omeka\Job\Strategy\PhpCliStrategy;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class PhpCliFactory implements FactoryInterface
{
    /**
     * Create the PhpCli strategy service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return PhpCliStrategy
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
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
