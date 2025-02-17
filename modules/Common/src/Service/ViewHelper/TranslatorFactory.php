<?php declare(strict_types=1);

namespace Common\Service\ViewHelper;

use Common\View\Helper\Translator;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TranslatorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Translator(
            $services->get(\Laminas\I18n\Translator\TranslatorInterface::class)->getDelegatedTranslator()
        );
    }
}
