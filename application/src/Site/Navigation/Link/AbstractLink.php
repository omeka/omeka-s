<?php
namespace Omeka\Site\Navigation\Link;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractLink implements LinkInterface
{
    use ServiceLocatorAwareTrait;
}
