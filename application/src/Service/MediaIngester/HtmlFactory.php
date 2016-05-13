<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\Html;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HtmlFactory implements FactoryInterface
{
    /**
     * Create the Html media ingester service.
     *
     * @param ServiceLocatorInterface $mediaIngesterServiceLocator
     * @return Html
     */
    public function createService(ServiceLocatorInterface $mediaIngesterServiceLocator)
    {
        $serviceLocator = $mediaIngesterServiceLocator->getServiceLocator();
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new Html($htmlPurifier);
    }
}
