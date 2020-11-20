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
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $navTranslator = $serviceLocator->get('Omeka\Site\NavigationTranslator');
        return new ListOfPages($navTranslator);
    }
}
