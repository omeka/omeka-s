<?php
namespace Omeka\Media\Renderer;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractRenderer implements RendererInterface
{
    use ServiceLocatorAwareTrait;
}
