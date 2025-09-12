<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\Exception;
use Laminas\Form\ElementInterface;
use Laminas\Form\Element\Select as SelectElement;
use Laminas\Form\View\Helper\FormSelect as LaminasFormSelect;
use Omeka\Form\Element\SortTranslatedValueOptionsInterface;

class FormSelect extends LaminasFormSelect
{
    /**
     * Override parent::render(), copying over the original code, adding a few
     * lines that disable/enable automatic translation and translate/sort the
     * value options.
     */
    public function render(ElementInterface $element): string
    {
        if (! $element instanceof SelectElement) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the element is of type Laminas\Form\Element\Select',
                __METHOD__
            ));
        }

        $name = $element->getName();
        if ($name === null || $name === '') {
            throw new Exception\DomainException(sprintf(
                '%s requires that the element has an assigned name; none discovered',
                __METHOD__
            ));
        }

        /* begin code injection */
        if ($element instanceof SortTranslatedValueOptionsInterface) {
            // Temporarily disable the translator.
            $this->setTranslatorEnabled(false);
            $options = $this->getValueOptions($element);
        } else {
            $options = $element->getValueOptions();
        }
        /* end code injection */

        if (($emptyOption = $element->getEmptyOption()) !== null) {
            $options = ['' => $emptyOption] + $options;
        }

        $attributes = $element->getAttributes();
        $value      = $this->validateMultiValue($element->getValue(), $attributes);

        $attributes['name'] = $name;
        if (array_key_exists('multiple', $attributes) && $attributes['multiple']) {
            $attributes['name'] .= '[]';
        }
        $this->validTagAttributes = $this->validSelectAttributes;

        $rendered = sprintf(
            '<select %s>%s</select>',
            $this->createAttributesString($attributes),
            $this->renderOptions($options, $value)
        );

        // Render hidden element
        if ($element->useHiddenElement()) {
            $rendered = $this->renderHiddenElement($element) . $rendered;
        }

        /* begin code injection */
        // Enable the translator (default is true)
        $this->setTranslatorEnabled(true);
        /* end code injection */

        return $rendered;
    }

    /**
     * Get the translated/sorted value options.
     */
    public function getValueOptions(ElementInterface $element)
    {
        $options = $element->getValueOptions();
        $view = $this->getView();

        // Translate the options labels.
        foreach ($options as &$option) {
            if (is_string($option)) {
                $option = $view->translate($option);
            } elseif (is_array($option)) {
                $option['label'] = $view->translate($option['label']);
                if (isset($option['options'])) {
                    foreach ($option['options'] as &$groupOption) {
                        if (is_string($groupOption)) {
                            $groupOption = $view->translate($groupOption);
                        } elseif (is_array($groupOption)) {
                            $groupOption['label'] = $view->translate($groupOption['label']);
                        }
                    }
                }
            }
        }

        // Function to get option labels.
        $getLabel = function ($option) {
            if (is_string($option)) {
                return $option;
            } elseif (is_array($option)) {
                return $option['label'];
            }
        };
        // Sort the options alphabetically.
        uasort($options, function ($a, $b) use ($getLabel) {
            return strcasecmp($getLabel($a), $getLabel($b));
        });
        foreach ($options as &$option) {
            if (isset($option['options'])) {
                uasort($option['options'], function ($a, $b) use ($getLabel) {
                    return strcasecmp($getLabel($a), $getLabel($b));
                });
            }
        }

        // Elements can finalize the value options.
        $options = $element->getFinalizedValueOptions($options);

        return $options;
    }
}
