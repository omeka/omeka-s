<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Locale;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LocaleFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator')->getTranslator()->getDelegatedTranslator();
        return new Locale($translator->getLocale());
    }
}
