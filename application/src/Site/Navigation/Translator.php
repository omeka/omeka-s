<?php
namespace Omeka\Site\Navigation;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\Manager as LinkManager;

class Translator
{
    /**
     * @var LinkManager
     */
    protected $linkManager;

    public function __construct(LinkManager $linkManager)
    {
        $this->linkManager = $linkManager;
    }

    /**
     * Translate Omeka site navigation to Zend Navigation format.
     *
     * @param Site $site
     * @return array
     */
    public function toZend(SiteRepresentation $site)
    {
        $manager = $this->linkManager;
        $buildLinks = function ($linksIn) use (&$buildLinks, $site, $manager)
        {
            $linksOut = [];
            foreach ($linksIn as $key => $data) {
                $linkType = $manager->get($data['type']);
                $linksOut[$key] = $linkType->toZend($data['data'], $site);
                $linksOut[$key]['label'] = $linkType->getLabel($data['data'], $site);
                if (isset($data['links'])) {
                    $linksOut[$key]['pages'] = $buildLinks($data['links']);
                }
            }
            return $linksOut;
        };
        $links = $buildLinks($site->navigation());
        if (!$links) {
            // The site must have at least one page for navigation to work.
            $links = [[
                'label' => 'Home',
                'route' => 'site',
                'params' => [
                    'site-slug' => $site->slug(),
                ],
            ]];
        }
        return $links;
    }

    /**
     * Translate Omeka site navigation to jsTree node format.
     *
     * @param SiteRepresentation $site
     * @return array
     */
    public function toJstree(SiteRepresentation $site)
    {
        $manager = $this->linkManager;
        $buildLinks = function ($linksIn) use (&$buildLinks, $site, $manager)
        {
            $linksOut = [];
            foreach ($linksIn as $data) {
                $linkType = $manager->get($data['type']);
                $linkLabel = $linkType->getLabel($data['data'], $site);
                $linkData = $linkType->toJstree($data['data'], $site);
                $linksOut[] = [
                    'text' => $linkLabel,
                    'data' => [
                        'type' => $data['type'],
                        'data' => $linkData,
                    ],
                    'children' => $data['links'] ? $buildLinks($data['links']) : [],
                ];
            }
            return $linksOut;
        };
        $links = $buildLinks($site->navigation());
        return $links;
    }

    /**
     * Translate jsTree node format to Omeka site navigation.
     *
     * @param array $jstree
     * @return array
     */
    public function fromJstree(array $jstree)
    {
        $buildPages = function ($pagesIn) use (&$buildPages) {
            $pagesOut = [];
            foreach ($pagesIn as $page) {
                if (isset($page['data']['remove']) && $page['data']['remove']) {
                    // Remove pages set to be removed.
                    continue;
                }
                $pagesOut[] = [
                    'type' => $page['data']['type'],
                    'data' => $page['data']['data'],
                    'links' => $page['children'] ? $buildPages($page['children']) : [],
                ];
            }
            return $pagesOut;
        };
        return $buildPages($jstree);
    }
}
