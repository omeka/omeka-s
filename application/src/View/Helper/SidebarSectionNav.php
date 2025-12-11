<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class SidebarSectionNav extends AbstractHelper
{
    /**
     * Return markup for tabbed sidebar section nav.
     *
     * @param array $navSpec Tab labels keyed by corresponding section ID
     */
    public function __invoke(array $navSpec, $tablistId = '')
    {
        $view = $this->getView();
        $listItems = [];
        $i = 0;
        foreach ($navSpec as $id => $label) {
            $isActive = (0 === $i++);
            $listItems[] = sprintf(
                '<button type="button" class="%s" data-id="%s" id="%s" role="tab" aria-selected="%s" aria-controls="%s">%s</button>',
                ($isActive) ? 'active' : '',
                $view->escapeHtml($id),
                $view->escapeHtml($id) . '-label',
                ($isActive) ? 'true' : 'false',
                $view->escapeHtml($id),
                $view->escapeHtml($label)
            );
        }
        return sprintf('<div class="sidebar-section-nav" role="tablist" aria-label="%s">%s</div>', $tablistId, implode('', $listItems));
    }
}
