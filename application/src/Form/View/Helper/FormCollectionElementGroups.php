<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\View\Helper\FormCollection;

class FormCollectionElementGroups extends FormCollection
{
    /**
     * Render all the elements of a form/fieldset, with the option of using
     * element groups instead of Laminas fieldsets.
     *
     * Element groups render similarly to Laminas fieldsets; they differ in how
     * they name their constituent elements, and therefore how they're organized
     * after form submission. For fieldsets that have an "element_groups"
     * option, this helper will flatten the fieldsets, retain the original
     * element names, and wrap element groups with a fieldset.
     *
     * @param ElementInterface $element
     * @return string
     */
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
            $markup .= '<fieldset>';
            $markup .= sprintf('<legend>%s</legend>', $view->escapeHtml($view->translate($elementGroupLabel)));
            foreach ($elementsInGroups[$elementGroupName] as $elementInGroups) {
                $markup .= $view->formRow($elementInGroups);
            }
            $markup .= '</fieldset>';
        }
        return $markup;
    }

    /**
     * Organize elements into in-group and not-in-group.
     *
     * @param ElementInterface $element
     * @param array $elementGroups
     * @param array &$elementsInGroups
     * @param array &$elementsNotInGroups
     */
    public function groupElements(ElementInterface $element, array $elementGroups, array &$elementsInGroups, array &$elementsNotInGroups)
    {
        foreach ($element->getIterator() as $elementOrFieldset) {
            if ($elementOrFieldset instanceof FieldsetInterface) {
                $this->groupElements($elementOrFieldset, $elementGroups, $elementsInGroups, $elementsNotInGroups);
            } elseif ($elementOrFieldset instanceof ElementInterface) {
                $elementGroupName = $elementOrFieldset->getOption('element_group');
                if ($elementGroupName && array_key_exists($elementGroupName, $elementGroups)) {
                    // This element belongs to a registered group.
                    $elementsInGroups[$elementGroupName][] = $elementOrFieldset;
                } else {
                    // This element does not belong to a registered group.
                    $elementsNotInGroups[] = $elementOrFieldset;
                }
            }
        }
    }
}
