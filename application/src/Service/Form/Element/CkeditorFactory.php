<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\Ckeditor;
use Zend\ServiceManager\Factory\FactoryInterface;

class CkeditorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new Ckeditor;
        $element->setHtmlPurifier($services->get('Omeka\HtmlPurifier'));
        return $element;
    }
}
