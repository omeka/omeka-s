<?php
namespace Omeka\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Omeka\Site\BlockLayout\RawHtml;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RawHtmlFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $htmlPurifier = $serviceLocator->get('Omeka\HtmlPurifier');
        return new RawHtml($htmlPurifier);
    }
}
