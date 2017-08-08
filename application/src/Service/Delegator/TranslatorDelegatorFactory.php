<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class TranslatorDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        return new Translator($callback());
    }
}
