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
}
