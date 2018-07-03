<?php
namespace Omeka\Site\Navigation;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Site\Navigation\Link\Manager as LinkManager;
use Zend\Mvc\I18n\Translator as I18n;
use Zend\View\Helper\Url;

class Translator
{
    /**
     * @var LinkManager
     */
    protected $linkManager;

    /**
     * @var I18n
     */
    protected $i18n;

    /**
     * @var Url
     */
    protected $urlHelper;

    public function __construct(LinkManager $linkManager, I18n $i18n, Url $urlHelper)
    {
        $this->linkManager = $linkManager;
        $this->i18n = $i18n;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Translate Omeka site navigation to Zend Navigation format.
     *
     * @param SiteRepresentation $site
     * @return array
     */
    public function toZend(SiteRepresentation $site)
    {
        $manager = $this->linkManager;
        $buildLinks = function ($linksIn) use (&$buildLinks, $site, $manager) {
            $linksOut = [];
            foreach ($linksIn as $key => $data) {
                $linkType = $manager->get($data['type']);
                $linkData = $data['data'];
                $linksOut[$key] = $linkType->toZend($linkData, $site);
                $linksOut[$key]['label'] = $this->getLinkLabel($linkType, $linkData, $site);
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
                'label' => $this->i18n->translate('Home'),
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
        $buildLinks = function ($linksIn) use (&$buildLinks, $site, $manager) {
            $linksOut = [];
            foreach ($linksIn as $data) {
                $linkType = $manager->get($data['type']);
                $linkData = $data['data'];
                $linksOut[] = [
                    'text' => $this->getLinkLabel($linkType, $data['data'], $site),
                    'data' => [
                        'type' => $data['type'],
                        'data' => $linkType->toJstree($linkData, $site),
                        'url' => $this->getLinkUrl($linkType, $data, $site),
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

    /**
     * Get the label for a link.
     *
     * User-provided labels should be used as-is, while system-provided "backup" labels
     * should be translated.
     *
     * @param LinkInterface $link
     * @param array $data
     * @param SiteRepresentation $site
     * @return string
     */
    public function getLinkLabel(LinkInterface $linkType, array $data, SiteRepresentation $site)
    {
        $label = $linkType->getLabel($data, $site);
        if ($label) {
            return $label;
        }
        return $this->i18n->translate($linkType->getName());
    }

    /**
     * Get the url for a link.
     *
     * @param LinkInterface $link
     * @param array $data
     * @param SiteRepresentation $site
     * @return string
     */
    public function getLinkUrl(LinkInterface $linkType, array $data, SiteRepresentation $site)
    {
        $linkZend = $linkType->toZend($data['data'], $site);
        if (array_key_exists('uri', $data)) {
            return $data['uri'];
        }
        if (empty($linkZend['route'])) {
            return '';
        }
        $urlRoute = $linkZend['route'];
        $urlParams = empty($linkZend['params']) ? [] : $linkZend['params'];
        $urlParams['site-slug'] = $site->slug();
        $urlOptions = empty($linkZend['query']) ? [] : ['query' => $linkZend['query']];
        $urlHelper = $this->urlHelper;
        $url = $urlHelper($urlRoute, $urlParams, $urlOptions);
        return $url;
    }
}
