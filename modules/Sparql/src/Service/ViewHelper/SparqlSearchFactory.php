<?php declare(strict_types=1);

namespace Sparql\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Sparql\View\Helper\SparqlSearch;

/**
 * Service factory for the SparqlSearch view helper.
 */
class SparqlSearchFactory implements FactoryInterface
{
    /**
     * Create and return the SparqlSearch view helper.
     *
     * @return SparqlSearch
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        $basePath = $config['file_store']['local']['base_path'] ?: OMEKA_PATH . '/files';
        $plugins = $services->get('ControllerPluginManager');
        return new SparqlSearch(
            $services->get('Omeka\Connection'),
            $plugins->get('currentSite'),
            $services->get('FormElementManager'),
            $plugins->get('messenger'),
            $plugins->get('params'),
            $services->get('Omeka\Settings'),
            $basePath,
            (int) $config['sparql']['config']['sparql_limit_per_page']
        );
    }
}
