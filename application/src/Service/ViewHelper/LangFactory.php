<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Lang;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LangFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator')->getTranslator()->getDelegatedTranslator();
        return new Lang($translator);
    }
}
