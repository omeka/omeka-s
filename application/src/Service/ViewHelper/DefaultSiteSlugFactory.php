<?php
namespace Omeka\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Omeka\View\Helper\DefaultSiteSlug;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory to get the default site slug, or the first site slug.
 *
 * @todo Store the default site as slug instead of id?
 * @todo Set a setting for the default site of the user?
 */
class DefaultSiteSlugFactory implements FactoryInterface
{
    /**
     * Create and return the DefaultSiteSlug view helper.
     *
     * @return DefaultSiteSlug
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $defaultSiteId = $services->get('Omeka\Settings')->get('default_site');
        $api = $services->get('Omeka\ApiManager');
        if ($defaultSiteId) {
            $slugs = $api->search('sites', ['id' => $defaultSiteId], ['returnScalar' => 'slug'])->getContent();
        } else {
            $slugs = $api->search('sites', ['limit' => 1], ['returnScalar' => 'slug'])->getContent();
        }
        $defaultSiteSlug = (string) reset($slugs);
        return new DefaultSiteSlug($defaultSiteSlug);
    }
}
