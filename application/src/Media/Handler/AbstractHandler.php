<?php
namespace Omeka\Media\Handler;

use Omeka\Media\Handler\HandlerInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractHandler implements HandlerInterface
{
    use ServiceLocatorAwareTrait;
}
