<?php
namespace Omeka\Block\Handler;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractHandler implements HandlerInterface
{
    use ServiceLocatorAwareTrait;
}
