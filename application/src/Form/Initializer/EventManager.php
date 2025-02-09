<?php
namespace Omeka\Form\Initializer;

use Interop\Container\ContainerInterface;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Form\Form;
use Laminas\ServiceManager\Initializer\InitializerInterface;

/**
 * Initializer to automatically inject a EventManager in any form or form
 * element implementing EventManagerAwareInterface.
 *
 * An injected event manager is necessary for shared event listener support
 * and therefore the ability of modules to listen to the form or element's
 * events.
 */
class EventManager implements InitializerInterface
{
    public function __invoke(ContainerInterface $container, $form)
    {
        if (!$form instanceof EventManagerAwareInterface) {
            return;
        }

        // Set a new EventManager if the form either doesn't have one or has one
        // without a connection to the shared manager, which is the usual situation
        // if using EventManagerAwareTrait
        $eventManager = $form->getEventManager();
        if ($eventManager instanceof EventManagerInterface
            && $eventManager->getSharedManager() instanceof SharedEventManagerInterface
        ) {
            return;
        }

        $form->setEventManager($container->get('EventManager'));
    }
}
