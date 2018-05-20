<?php
namespace Omeka\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Omeka\View\Helper\PublicResourceUrl;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory for the PublicResourceUrlFactory view helper.
 *
 * @todo Set a setting for the default site of the user.
 */
class PublicResourceUrlFactory implements FactoryInterface
{
    /**
     * Create and return the PublicResourceUrlFactory view helper
     *
     * @return PublicResourceUrlFactory
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // Get the slug for the default site, else the first one.
        $defaultSiteId = $services->get('Omeka\Settings')->get('default_site');
        $api = $services->get('Omeka\ApiManager');
        if ($defaultSiteId) {
            $slugs = $api->search('sites', ['id' => $defaultSiteId], ['returnScalar' => 'slug'])->getContent();
        } else {
            $slugs = $api->search('sites', ['limit' => 1], ['returnScalar' => 'slug'])->getContent();
        }
        $defaultSiteSlug = reset($slugs);
        return new PublicResourceUrl($defaultSiteSlug);
    }
}
