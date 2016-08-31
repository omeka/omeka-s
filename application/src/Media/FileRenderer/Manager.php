<?php
namespace Omeka\Media\FileRenderer;

use Zend\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $instanceOf = RendererInterface::class;
}
