<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\Navigation\Translator;
use Laminas\Form\Element\Hidden;
use Laminas\View\Renderer\PhpRenderer;

class ListOfPages extends AbstractBlockLayout
{
    /**
     * @var Translator
     */
    protected $navTranslator;

    public function __construct(Translator $navTranslator)
    {
        $this->navTranslator = $navTranslator;
    }

    public function getLabel()
    {
        return 'List of pages'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('vendor/jstree/jstree.min.js', 'Omeka'));
        $view->headLink()->appendStylesheet($view->assetUrl('css/jstree.css', 'Omeka'));
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $escape = $view->plugin('escapeHtml');
        $pageList = new Hidden("o:block[__blockIndex__][o:data][pagelist]");
        $pageList->setValue($block ? $block->dataValue('pagelist') : json_encode($this->navTranslator->toJstree($site)));

        $html = '<div class="block-pagelist-tree"';
        $html .= 'data-link-form-url="' . $escape($view->url('admin/site/slug/action', ['action' => 'navigation-link-form'], true));
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

        $iterate = function ($linksIn, $depth = 0) use (&$iterate, &$listOfPages) {
            foreach ($linksIn as $key => $data) {
                $listOfPages[] = [
                  'pagename' => $data['text'],
                  'pagelink' => $data['data']['url'],
                  'depth' => $depth,
                ];
                if (isset($data['children'])) {
                    $iterate($data['children'], $depth + 1);
                }
            }
        };
        $iterate($pageList);

        return $view->partial('common/block-layout/list-of-pages', [
            'pagelist' => $listOfPages,
        ]);
    }
}
