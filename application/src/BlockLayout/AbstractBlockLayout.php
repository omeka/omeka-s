<?php
namespace Omeka\BlockLayout;

use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractBlockLayout implements BlockLayoutInterface
{
    use ServiceLocatorAwareTrait;
}
