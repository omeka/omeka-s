<?php
namespace Omeka\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Omeka\Site\BlockLayout\Html;
use Zend\ServiceManager\Factory\FactoryInterface;

class HtmlFactory implements FactoryInterface
{
    /**
     * Create the Html block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return Html
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new Html($htmlPurifier);
    }
}
