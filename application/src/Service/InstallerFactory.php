<?php
namespace Omeka\Service;

use Omeka\Installation\Installer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class InstallerFactory implements FactoryInterface
{
    /**
     * Create the installer service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Installer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['installer']['tasks'])) {
            throw new Exception\ConfigException('Missing installer configuration');
        }
        $installer = new Installer;
        foreach ($config['installer']['tasks'] as $task) {
            if (!class_exists($task)) {
                throw new Exception\ConfigException(sprintf(
                    'The "%s" installation task does not exist.', $task
                ));
            }
            if (!is_subclass_of($task, 'Omeka\Installation\Task\TaskInterface')) {
                throw new Exception\ConfigException(sprintf(
                    'The "%s" task is not a valid installation task.', $task
                ));
            }
            $installer->registerTask($task);
        }
        return $installer;
    }
}
