<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\JsTranslate;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsTranslateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $translator = $services->get('MvcTranslator');
        $jsTranslations = [];
        foreach ($config['js_translate_strings'] as $jsString) {
            $jsTranslations[$jsString] = $translator->translate($jsString);
        }
        return new JsTranslate($jsTranslations);
    }
}
