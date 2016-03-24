<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Helper that renders section navigation.
 */
class SectionNav extends AbstractHelper
{
    /**
     * @param array $sectionNavs A list of section labels, keyed by nav ID.
     * @param string $event Name of the section_nav event to trigger, if any
     * @return string
     */
    public function __invoke(array $sectionNavs = array(), $sectionNavEvent = null)
    {
        $sectionNavArgs = ['section_nav' => $sectionNavs];
        if ($sectionNavEvent) {
            $sectionNavArgs = $this->getView()->trigger($sectionNavEvent, $sectionNavArgs, true);
        }

        $html = '<nav class="section-nav"><ul>';
        $firstId = key($sectionNavArgs['section_nav']);
        foreach ($sectionNavArgs['section_nav'] as $id => $label) {
            $html .= sprintf(
                '<li%s><a href="#%s">%s</a></li>',
                ($id === $firstId) ? ' class="active"' : null,
                $this->getView()->escapeHtml($id),
                $this->getView()->escapeHtml($label)
            );
        }
        $html .= '</ul></nav>';
        return $html;
    }
}
