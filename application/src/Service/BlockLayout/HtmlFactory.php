<?php
namespace Omeka\Service\BlockLayout;

use Omeka\Site\BlockLayout\Html;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HtmlFactory implements FactoryInterface
{
    /**
     * Create the Html block layout service.
     *
     * @param ServiceLocatorInterface $blockLayoutServiceLocator
     * @return Html
     */
    public function createService(ServiceLocatorInterface $blockLayoutServiceLocator)
    {
        $serviceLocator = $blockLayoutServiceLocator->getServiceLocator();
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new Html($htmlPurifier);
    }
}
