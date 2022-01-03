<?php declare(strict_types=1);

namespace Omeka\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\View\Helper\PublicResourceUrl;

/**
 * Service factory for the PublicResourceUrlFactory view helper.
 *
 * @todo Set a setting for the default site of the user.
 */
class PublicResourceUrlFactory implements FactoryInterface
{
    /**
     * Create and return the PublicResourceUrl view helper
     *
     * @return PublicResourceUrl
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new PublicResourceUrl(
            $services->get('Omeka\ApiManager')->search('sites', [], ['returnScalar' => 'slug'])->getContent(),
            $services->get('ViewHelperManager')->get('defaultSiteSlug')()
        );
    }
}
