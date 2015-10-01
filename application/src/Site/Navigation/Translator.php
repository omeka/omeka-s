<?php
namespace Omeka\Site\Navigation;

use Omeka\Entity\Site;
use Omeka\Api\Representation\SiteRepresentation;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Translator implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Translate site navigation to Zend navigation.
     *
     * @param Site $site
     * @return array
     */
    public function toZend(Site $site)
    {
        $manager = $this->getServiceLocator()->get('Omeka\Site\NavigationLinkManager');
        $buildLinks = function ($linksIn) use (&$buildLinks, $site, $manager)
        {
            $linksOut = array();
            foreach ($linksIn as $key => $data) {
                $linksOut[$key] = $manager->get($data['type'])->toZend($data, $site);
                if (isset($data['links'])) {
                    $linksOut[$key]['pages'] = $buildLinks($data['links']);
                }
            }
            return $linksOut;
        };
        $links = $buildLinks($site->getNavigation());
        if (!$links) {
            // The site must have at least one page for navigation to work.
            $links = array(array(
                'label' => 'Home',
                'route' => 'site',
                'params' => array(
                    'site-slug' => $site->getSlug(),
                ),
            ));
        }
        return $links;
    }

    public function toJstree(SiteRepresentation $site)
    {
        $manager = $this->getServiceLocator()->get('Omeka\Site\NavigationLinkManager');
        $buildLinks = function ($linksIn) use (&$buildLinks, $site, $manager)
        {
            $linksOut = array();
            foreach ($linksIn as $key => $data) {
                $linksOut[$key] = $manager->get($data['type'])->toJstree($data, $site);
                if (isset($data['links'])) {
                    $linksOut[$key]['children'] = $buildLinks($data['links']);
                }
            }
            return $linksOut;
        };
        $links = $buildLinks($site->navigation());
        return $links;
    }

    public function fromJstree(array $jstree)
    {
        $buildPages = function ($pagesIn) use (&$buildPages) {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                if (isset($page['data']['remove']) && $page['data']['remove']) {
                    // Remove pages set to be removed.
                    continue;
                }
                $pagesOut[$key] = $page['data'];
                if ($page['children']) {
                    $pagesOut[$key]['links'] = $buildPages($page['children']);
                }
            }
            return $pagesOut;
        };
        return $buildPages($jstree);
    }
}
