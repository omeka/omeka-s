<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Navigation\Navigation;
use Zend\Form\Element\Number;
use Zend\View\Renderer\PhpRenderer;

class TableOfContents extends AbstractBlockLayout
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Table of Contents');
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        $depth = new Number("o:block[__blockIndex__][o:data][depth]");

        if ($block) {
            $depth->setAttribute('value', $this->getData($block->data(), 'depth'));
        }
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

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $view->pageViewModel->setVariable('displayNavigation', false);

        $nav =  $view->navigation('Zend\Navigation\Site');
        $container = $nav->getContainer();
        $activePage = $nav->findActive($container);
        $pages = $activePage['page']->getPages();
        $subNav = new Navigation($pages);

        $depth = $this->getData($block->data(), 'depth');
        if (!isset($depth)) {
            $depth = 1;
        }

        $html = '';
        $html .= '<div class="toc-block">';
        $html .= $view->navigation($subNav)->menu()->renderMenu(null,
            array(
                'maxDepth' => $depth - 1
            )
        );
        $html .= '</div>';

        return $html;
    }
}
