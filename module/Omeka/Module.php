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
        $manager = $event->getApplication()->getEventManager()->getSharedManager();
        $manager->attach('Omeka\Api\Adapter\Entity\PropertyAdapter', 'search.pre',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
        $manager->attach('Omeka\Api\Adapter\Entity\PropertyAdapter', 'search.post',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
        $manager->attach('Omeka\Api\Manager', 'execute.pre',
            function($e) {
                printf(
                    'Handled event "%s" on target "%s", with parameters %s',
                    $e->getName(),
                    get_class($e->getTarget()),
                    json_encode($e->getParams())
                );
            }
        );
        $manager->attach('Omeka\Api\Manager', 'execute.post',
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
