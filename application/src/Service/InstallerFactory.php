<?php
namespace Omeka\Service;

use Omeka\Installation\Installer;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class InstallerFactory implements FactoryInterface
{
    /**
     * Create the installer service.
     *
     * @return Installer
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['installer']['tasks'])) {
            throw new Exception\ConfigException('Missing installer configuration');
        }
        $installer = new Installer($serviceLocator);
        foreach ($config['installer']['pre_tasks'] as $task) {
            $this->validateTask($task);
            $installer->registerPreTask($task);
        }
        foreach ($config['installer']['tasks'] as $task) {
            $this->validateTask($task);
            $installer->registerTask($task);
        }
        return $installer;
    }

    protected function validateTask($task)
    {
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
    }
}
