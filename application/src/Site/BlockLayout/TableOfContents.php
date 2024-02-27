<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Laminas\Navigation\Navigation;
use Laminas\Form\Element\Number;
use Laminas\View\Renderer\PhpRenderer;

class TableOfContents extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    public function getLabel()
    {
        return 'Table of contents'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $depth = new Number("o:block[__blockIndex__][o:data][depth]");

        $depthValue = 1;
        if ($block) {
            $blockDepth = (int) $block->dataValue('depth');
            if ($blockDepth > 1) {
                $depthValue = $blockDepth;
            }
        }
        $depth->setValue($depthValue);
        $depth->setAttribute('min', 1);

        $html = '';
        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label>' . $view->translate('Depth') . '</label>';
        $html .= '<div class="field-description">' . $view->translate('Number of child page levels to display') . '</div>';
        $html .= '</div>';
        $html .= '<div class="inputs">' . $view->formRow($depth) . '</div>';
        $html .= '</div>';
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/table-of-contents')
    {
        $view->pageViewModel->setVariable('displayNavigation', false);

        $nav = $block->page()->site()->publicNav();
        $container = $nav->getContainer();
        $activePage = $nav->findActive($container);

        if (!$activePage) {
            return null;
        }

        // Make new copies of the pages so we don't disturb the regular nav
        $pages = $activePage['page']->getPages();
        $newPages = [];
        foreach ($pages as $page) {
            $newPages[] = $page->toArray();
        }
        $subNav = new Navigation($newPages);

        // Don't use dataValue's default here; we need to handle empty/non-numerics anyway
        $depth = (int) $block->dataValue('depth');
        if ($depth < 1) {
            $depth = 1;
        }

        return $view->partial($templateViewScript, [
            'block' => $block,
            'subNav' => $subNav,
            'maxDepth' => $depth - 1,
        ]);
    }
}
