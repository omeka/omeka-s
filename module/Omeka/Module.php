<?php
namespace Omeka;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(EventInterface $event)
    {
        $manager = $event->getApplication()->getServiceManager()
            ->get('SharedEventManager');
        $manager->attach('Omeka\Api\Adapter\Entity\PropertyAdapter', 'preSearch',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
        $manager->attach('Omeka\Api\Adapter\Entity\PropertyAdapter', 'postSearch',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
        $manager->attach('Omeka\Api\Manager', 'preExecute',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
        $manager->attach('Omeka\Api\Manager', 'postExecute',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
    }
}
