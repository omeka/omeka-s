<?php declare(strict_types=1);

namespace Common\Service\ViewHelper;

use Common\View\Helper\DefaultSite;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

/**
 * Service factory to get default site, or the first public, or the first one.
 */
class DefaultSiteFactory implements FactoryInterface
{
    /**
     * Create and return the DefaultSite view helper.
     *
     * @return DefaultSite
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $site = null;
        $api = $services->get('Omeka\ApiManager');
        $defaultSiteId = $services->get('Omeka\Settings')->get('default_site');
        if ($defaultSiteId) {
            try {
                $site = $api->read('sites', ['id' => $defaultSiteId])->getContent();
            } catch (\Exception $e) {
                // Nothing.
            }
        }
        // Fix issues after Omeka install without public site, so very rarely.
        if (empty($site)) {
            // Search first public site first.
            $sites = $api->search('sites', ['is_public' => true, 'limit' => 1])->getContent();
            $site = $sites ? reset($sites) : null;
            // Else first site.
            if (empty($sites)) {
                $sites = $api->search('sites', ['limit' => 1])->getContent();
                $site = $sites ? reset($sites) : null;
            }
        }
        return new DefaultSite($site);
    }
}
