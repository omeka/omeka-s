<?php
namespace Omeka\Thumbnailer\Strategy;

use Omeka\Thumbnailer\Strategy\StrategyInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class GdStrategy implements StrategyInterface
{
    use ServiceLocatorAwareTrait;
}
