<?php
namespace Omeka\Service\Delegator;

use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Interop\Container\ContainerInterface;
use Omeka\View\Helper\Url;

class UrlDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        return new Url($callback());
    }
}
