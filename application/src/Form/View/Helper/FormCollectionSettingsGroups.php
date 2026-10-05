<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;

/**
 * Render element groups on the settings pages.
 *
 * Each group gets an ID and a class for the settings filter. Core's settings
 * forms record their own groups in the "core_element_groups" option; on those,
 * any other group was added by a module and is labeled as such. Forms that
 * don't record them, such as a theme's settings, get no labels.
 */
class FormCollectionSettingsGroups extends FormCollectionElementGroups
{
    /**
     * On core's settings forms, settings without a group go last, in a group
     * of their own, so they don't read as part of the group before them and a
     * chip can reach them. Core groups all of its own settings, so these come
     * from modules. Hidden inputs, such as the CSRF token, and unlabeled
     * elements still render first, outside any group.
     *
     * Other forms, such as a theme's settings, keep ungrouped settings first,
     * where their author put them.
     */
    protected function renderGroups(ElementInterface $element, array $elementGroups, array $elementsInGroups, array $elementsNotInGroups): string
    {
        if (!is_array($element->getOption('core_element_groups'))) {
            return parent::renderGroups($element, $elementGroups, $elementsInGroups, $elementsNotInGroups);
        }
        $outside = [];
        foreach ($elementsNotInGroups as $elementNotInGroups) {
            if ('hidden' === $elementNotInGroups->getAttribute('type') || !$elementNotInGroups->getLabel()) {
                $outside[] = $elementNotInGroups;
            } else {
                $elementsInGroups['other'][] = $elementNotInGroups;
            }
        }
        if (isset($elementsInGroups['other']) && !isset($elementGroups['other'])) {
            $elementGroups['other'] = 'Other'; // @translate
        }
        return parent::renderGroups($element, $elementGroups, $elementsInGroups, $outside);
    }

    protected function renderGroup(ElementInterface $element, $groupName, $groupLabel, array $groupElements): string
    {
        $view = $this->getView();
        $coreGroups = $element->getOption('core_element_groups');
        $isModule = is_array($coreGroups) && !in_array($groupName, $coreGroups, true);
        $markup = sprintf(
            '<fieldset id="settings-group-%s" class="%s">',
            $view->escapeHtmlAttr($groupName),
            $isModule ? 'settings-group settings-group-module' : 'settings-group'
        );
        $markup .= sprintf('<legend><h2 class="fieldsets-heading">%s</h2>', $view->escapeHtml($view->translate($groupLabel)));
        if ($isModule) {
            $markup .= sprintf('<span class="settings-group-module-label">%s</span>', $view->escapeHtml($view->translate('Module')));
        }
        $markup .= '</legend>';
        $markup .= $this->renderRows($groupElements);
        $markup .= '</fieldset>';
        return $markup;
    }
}
