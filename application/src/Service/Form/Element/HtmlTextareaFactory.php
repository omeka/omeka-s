<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\HtmlTextarea;
use Zend\ServiceManager\Factory\FactoryInterface;

class HtmlTextareaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new HtmlTextarea;
        $element->setHtmlPurifier($services->get('Omeka\HtmlPurifier'));
        return $element;
    }
}
