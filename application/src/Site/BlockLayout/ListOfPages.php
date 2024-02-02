<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\Navigation\Link\Manager as LinkManager;
use Omeka\Site\Navigation\Translator;
use Laminas\Form\Element\Hidden;
use Laminas\View\Renderer\PhpRenderer;

class ListOfPages extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
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
            $nodes = json_decode($block->dataValue('pagelist'), true);
            $nodes = is_array($nodes) ? $nodes : [];
            $pageTree = $this->getPageNodeURLs($nodes, $block);
        } else {
            $pageTree = '';
        }
        $pageList->setValue(json_encode($pageTree));

        $html = '<div class="block-pagelist-tree" data-jstree-data="' . $escape($pageList->getValue()) . '"></div>';
        $html .= '<button type="button" class="site-page-add">' . $view->translate('Add pages') . '</button>';
        $html .= '<div class="inputs">' . $view->formRow($pageList) . '</div>';

        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/list-of-pages')
    {
        $nodes = json_decode($block->dataValue('pagelist'), true);
        if (!is_array($nodes)) {
            return '';
        }

        $pageTree = $this->getPageNodeURLs($nodes, $block);

        return $view->partial($templateViewScript, [
            'block' => $block,
            'pageList' => $pageTree,
        ]);
    }

    public function getPageNodeURLs($nodes, SitePageBlockRepresentation $block)
    {
        $site = $block->page()->site();
        $pages = $site->pages();

        // Add page URL to jstree node data if not already present
        $iterate = function (&$value, $key) use (&$iterate, $site, $pages) {
            if (is_array($value)) {
                if (array_key_exists('type', $value)) {
                    $isPublic = isset($pages[$value['data']['id']]) ? $pages[$value['data']['id']]->isPublic() : true;
                    $manager = $this->linkManager;
                    $linkType = $manager->get($value['type']);
                    $linkData = $value;
                    $pageUrl = $this->navTranslator->getLinkUrl($linkType, $linkData, $site);
                    $value['url'] = $pageUrl;
                    $value['data']['is_public'] = $isPublic;
                }
                array_walk($value, $iterate);
            }
        };
        array_walk($nodes, $iterate);
        return $nodes;
    }
}
