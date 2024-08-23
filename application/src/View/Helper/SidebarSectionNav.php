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
    public function __invoke(array $navSpec)
    {
        $view = $this->getView();
        $listItems = [];
        $i = 0;
        foreach ($navSpec as $id => $label) {
            $listItems[] = sprintf(
                '<li class="%s"><button type="button" data-id="%s">%s</button></li>',
                0 === $i++ ? 'active' : '',
                $view->escapeHtml($id),
                $view->escapeHtml($label)
            );
        }
        return sprintf('<div class="sidebar-section-nav"><ul>%s</ul></div>', implode('', $listItems));
    }
}
