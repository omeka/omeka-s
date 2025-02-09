<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Omeka\I18n\Translator;
use Laminas\I18n\Translator\Loader;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

class TranslatorDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $translator = $callback();
        $translator->getPluginManager()->setFactory(Loader\Gettext::class, function ($loaders) {
            $loader = new \Omeka\I18n\GettextLoader;
            return $loader;
        });
        return new Translator($translator);
    }
}
