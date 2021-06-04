<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\Navigation\Link\Manager as LinkManager;
use Omeka\Site\Navigation\Translator;
use Laminas\Form\Element\Hidden;
use Laminas\View\Renderer\PhpRenderer;

class ListOfPages extends AbstractBlockLayout
{
    /**
     * @var LinkManager
     */
    protected $linkManager;

    /**
     * @var Translator
     */
    protected $navTranslator;

    public function __construct(LinkManager $linkManager, Translator $navTranslator)
    {
        $this->linkManager = $linkManager;
        $this->navTranslator = $navTranslator;
    }

    public function getLabel()
    {
        return 'List of pages'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('vendor/jstree/jstree.min.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/jstree-plugins.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/list-of-pages-block-layout.js', 'Omeka'));
        $view->headLink()->appendStylesheet($view->assetUrl('css/jstree.css', 'Omeka'));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $escape = $view->plugin('escapeHtml');
        $pageList = new Hidden("o:block[__blockIndex__][o:data][pagelist]");
        if ($block) {
            // Add page URL to jstree node data if not already present
            $pageTree = json_decode($block->dataValue('pagelist'), true);
            $iterate = function (&$value, &$key) use (&$iterate, $site) {
                if (is_array($value)) {
                    if (array_key_exists('type', $value) && !array_key_exists('url', $value)) {
                        $manager = $this->linkManager;
                        $linkType = $manager->get($value['type']);
                        $linkData = $value;
                        $pageUrl = $this->navTranslator->getLinkUrl($linkType, $linkData, $site);
                        $value['url'] = $pageUrl;
                    }
                    array_walk($value, $iterate);
                }
            };
            array_walk($pageTree, $iterate);
        } else {
            $pageTree = '';
        }
        $pageList->setValue(json_encode($pageTree));

        $html = '<button type="button" class="site-page-add"';
        $html .= 'data-sidebar-content-url="' . $escape($page->url('sidebar-pagelist'));
        $html .= '">' . $view->translate('Add pages') . '</button>';
        $html .= '<div class="block-pagelist-tree"';
        $html .= '" data-jstree-data="' . $escape($pageList->getValue());
        $html .= '"></div><div class="inputs">' . $view->formRow($pageList) . '</div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $pageList = json_decode($block->dataValue('pagelist'), true);

        if (!$pageList) {
            return '';
        }

        return $view->partial('common/block-layout/list-of-pages', [
            'pageList' => $pageList,
        ]);
    }
}
