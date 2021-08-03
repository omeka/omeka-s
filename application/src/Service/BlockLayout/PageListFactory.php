<?php
namespace Omeka\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Omeka\Site\BlockLayout\ListOfPages;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageListFactory implements FactoryInterface
{
    /**
     * Create the listOfPages block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return ListOfPages
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ListOfPages(
            $services->get('Omeka\Site\NavigationLinkManager'),
            $services->get('Omeka\Site\NavigationTranslator')
        );
    }
}
