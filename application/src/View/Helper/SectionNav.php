<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering section navigation.
 */
class SectionNav extends AbstractHelper
{
    /**
     * Render section navigation.
     *
     * @param array $sectionNavs A list of section labels, keyed by nav ID.
     * @param null|string $sectionNavEvent Name of the section_nav event to trigger, if any
     * @param mixed $resource Resource represented by the show page, if any
     * @return string
     */
    public function __invoke(array $sectionNavs = [],
        $sectionNavEvent = null, $resource = null
    ) {
        $sectionNavArgs = [
            'section_nav' => $sectionNavs,
            'resource' => $resource,
        ];
        if ($sectionNavEvent) {
            $sectionNavArgs = $this->getView()->trigger($sectionNavEvent, $sectionNavArgs, true);
        }

        $html = '<nav class="section-nav"><ul>';
        $firstId = key($sectionNavArgs['section_nav']);
        foreach ($sectionNavArgs['section_nav'] as $id => $label) {
            $html .= sprintf(
                '<li%s><a href="#%s" id="%s-label">%s</a></li>',
                ($id === $firstId) ? ' class="active"' : null,
                $this->getView()->escapeHtml($id),
                $this->getView()->escapeHtml($id),
                $this->getView()->escapeHtml($label)
            );
        }
        $html .= '</ul></nav>';
        return $html;
    }
}
