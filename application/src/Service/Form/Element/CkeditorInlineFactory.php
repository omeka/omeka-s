<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\CkeditorInline;
use Zend\ServiceManager\Factory\FactoryInterface;

class CkeditorInlineFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new CkeditorInline;
        $element->setHtmlPurifier($services->get('Omeka\HtmlPurifier'));
        return $element;
    }
}
