<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

/**
 * Disable automatic translation for the <title> element.
 */
class HeadTitleDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $headTitle = $callback();
        $headTitle->setTranslatorEnabled(false);
        return $headTitle;
    }
}
