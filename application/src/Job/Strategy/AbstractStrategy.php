<?php
namespace Omeka\Job\Strategy;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractStrategy implements StrategyInterface
{
    use ServiceLocatorAwareTrait;
}
