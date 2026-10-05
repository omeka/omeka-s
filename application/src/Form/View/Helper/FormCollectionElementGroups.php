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
        $elementsInGroups = [];
        $elementsNotInGroups = [];
        $this->groupElements($element, $elementGroups, $elementsInGroups, $elementsNotInGroups);
        return $this->renderGroups($element, $elementGroups, $elementsInGroups, $elementsNotInGroups);
    }

    /**
     * Render elements that are not in groups, then the element groups.
     *
     * @param ElementInterface $element The form/fieldset that registers the groups
     * @param array $elementGroups
     * @param array $elementsInGroups
     * @param array $elementsNotInGroups
     * @return string
     */
    protected function renderGroups(ElementInterface $element, array $elementGroups, array $elementsInGroups, array $elementsNotInGroups): string
    {
        // First, render elements that are not in groups.
        $markup = $this->renderRows($elementsNotInGroups);
        // Then render elements that are in groups.
        foreach ($elementGroups as $elementGroupName => $elementGroupLabel) {
            if (!isset($elementsInGroups[$elementGroupName])) {
                // No elements belong to this group.
                continue;
            }
            $markup .= $this->renderGroup($element, $elementGroupName, $elementGroupLabel, $elementsInGroups[$elementGroupName]);
        }
        return $markup;
    }

    /**
     * Render one element group.
     *
     * @param ElementInterface $element The form/fieldset that registers the groups
     * @param string $groupName
     * @param mixed $groupLabel Whatever the translate helper accepts
     * @param array $groupElements
     * @return string
     */
    protected function renderGroup(ElementInterface $element, $groupName, $groupLabel, array $groupElements): string
    {
        $view = $this->getView();
        $markup = '<fieldset>';
        $markup .= sprintf('<legend><h2 class="fieldsets-heading">%s</h2></legend>', $view->escapeHtml($view->translate($groupLabel)));
        $markup .= $this->renderRows($groupElements);
        $markup .= '</fieldset>';
        return $markup;
    }

    /**
     * Render a row for each element.
     *
     * @param array $elements
     * @return string
     */
    protected function renderRows(array $elements): string
    {
        $view = $this->getView();
        $markup = '';
        foreach ($elements as $element) {
            $markup .= $view->formRow($element);
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
