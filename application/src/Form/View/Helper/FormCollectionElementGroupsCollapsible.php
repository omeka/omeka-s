<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;

class FormCollectionElementGroupsCollapsible extends FormCollectionElementGroups
{
    public function render(ElementInterface $element): string
    {
        $elementGroups = $element->getOption('element_groups');
        if (!$elementGroups || !is_array($elementGroups)) {
            // This form/fieldset has no registered element groups. Use the
            // default formCollection() behavior.
            return parent::render($element);
        }
        $view = $this->getView();
        $elementsInGroups = [];
        $elementsNotInGroups = [];
        $this->groupElements($element, $elementGroups, $elementsInGroups, $elementsNotInGroups);
        $markup = '';
        // First, render elements that are not in groups.
        foreach ($elementsNotInGroups as $elementNotInGroups) {
            $markup .= $view->formRow($elementNotInGroups);
        }
        // Then render elements that are in groups.
        foreach ($elementGroups as $elementGroupName => $elementGroupLabel) {
            if (!isset($elementsInGroups[$elementGroupName])) {
                // No elements belong to this group.
                continue;
            }
            $groupName = $view->escapeHtml($elementGroupName);
            $markup .= sprintf('<fieldset name="%s" id="%s" class="block-layout-fieldset" aria-labelledby="%s">', $groupName, $groupName, $groupName . '-label');
            $markup .= sprintf('<a href="#" class="expand" title="%s"><span class="fieldset-label" id="%s-label">%s</span></a>', $view->escapeHtml($view->translate('Expand')), $view->escapeHtml($elementGroupName), $view->escapeHtml($view->translate($elementGroupLabel)));
            $markup .= '<div class="collapsible">';
            foreach ($elementsInGroups[$elementGroupName] as $elementInGroups) {
                $markup .= $view->formRow($elementInGroups);
            }
            $markup .= '</div>';
            $markup .= '</fieldset>';
        }
        return $markup;
    }
}
