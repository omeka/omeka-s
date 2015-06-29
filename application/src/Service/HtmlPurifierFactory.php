<?php
namespace Omeka\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Omeka\Service\HtmlPurifier;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HtmlPurifierFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $purifier = new HtmlPurifier;
        $settings = $serviceLocator->get('Omeka\Settings');
        return new HtmlPurifier($settings->get('use_htmlpurifier'));
    }
}