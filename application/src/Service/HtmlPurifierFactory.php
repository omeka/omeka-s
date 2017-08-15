<?php
namespace Omeka\Service;

use Omeka\Stdlib\HtmlPurifier;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HtmlPurifierFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $settings = $serviceLocator->get('Omeka\Settings');
        return new HtmlPurifier($settings->get('use_htmlpurifier'));
    }
}
