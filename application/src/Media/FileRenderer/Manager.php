<?php
namespace Omeka\Media\FileRenderer;

use Zend\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = RendererInterface::class;
}
