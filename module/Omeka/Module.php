<?php
namespace Omeka;

use Omeka\Module\AbstractModule;
use Zend\EventManager\EventInterface;
use Zend\EventManager\SharedEventManagerInterface;

/**
 * The Omeka module.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function attachShared(SharedEventManagerInterface $events)
    {
        $this->attachFilter(
            'Omeka\Api\Manager',
            'myFilterEvent',
            array($this, 'myFilterCallbackOne')
        );
        $this->attachFilter(
            'Omeka\Api\Manager',
            'myFilterEvent',
            array($this, 'myFilterCallbackTwo')
        );
        $this->attachEvent(
            'Omeka\Api\Manager',
            'execute.pre',
            array($this, 'myEventCallback')
        );
        $this->attachEvent(
            'Omeka\Api\Manager',
            'execute.post',
            array($this, 'myEventCallback')
        );
        $this->attachEvent(
            'Omeka\Api\Adapter\Entity\PropertyAdapter',
            'search.pre',
            array($this, 'myEventCallback')
        );
        $this->attachEvent(
            'Omeka\Api\Adapter\Entity\PropertyAdapter',
            'search.post',
            array($this, 'myEventCallback')
        );
    }

    public function myEventCallback(EventInterface $event)
    {
        printf(
            'Handled event "%s" on target "%s", with parameters %s',
            $event->getName(),
            get_class($event->getTarget()),
            json_encode($event->getParams())
        );
    }

    public function myFilterCallbackOne($arg, EventInterface $event)
    {
        $arg[] = 'bar';
        return $arg;
    }

    public function myFilterCallbackTwo($arg, EventInterface $event)
    {
        $arg[] = 'baz';
        return $arg;
    }
}
