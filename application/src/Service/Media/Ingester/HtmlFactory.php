<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\Html;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class HtmlFactory implements FactoryInterface
{
    /**
     * Create the Html media ingester service.
     *
     * @return Html
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Html($services->get('Omeka\HtmlPurifier'));
    }
}
