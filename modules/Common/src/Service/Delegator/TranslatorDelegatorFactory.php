<?php declare(strict_types=1);

namespace Common\Service\Delegator;

use Common\I18n\Translator;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;

/**
 * Override the Omeka TranslatorDelegatorFactory to manage PsrMessage.
 */
class TranslatorDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        /**
         * The callback is processed via Omeka TranslatorDelegatorFactory, so it
         * is useless to add the loaders.
         *
         * @see \Omeka\Service\Delegator\TranslatorDelegatorFactory
         * @see \Omeka\I18n\Translator
         */
        $translator = $callback();
        return new Translator($translator->getDelegatedTranslator());
    }
}
