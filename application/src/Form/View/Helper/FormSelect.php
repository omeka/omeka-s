<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormSelect as LaminasFormSelect;
use Laminas\Stdlib\ArrayUtils;
use Omeka\Form\Element\SelectSortTranslatedInterface;

class FormSelect extends LaminasFormSelect
{
    /**
     * @var ElementInterface
     */
    protected $element;

    // Override of parent::render().
    public function render(ElementInterface $element): string
    {
        // Temporarily set the select element so downstream code can detect
        // whether to sort value options after translating.
        $this->element = $element;
        $rendered = parent::render($element);
        $this->element = null;
        return $rendered;
    }

    // Override of parent::renderOptions().
    public function renderOptions(array $options, array $selectedOptions = []): string
    {
        // Implementations of SelectSortTranslatedInterface override default
        // behavior by sorting value options *after* they are translated.
        // Normally they are sorted before they are translated.
        $implementsSortTranslated = $this->element instanceof SelectSortTranslatedInterface;
        if ($implementsSortTranslated) {
            $options = $this->sortTranslated($options);
        }

        $template = '<option %s>%s</option>';
        $optionStrings = [];
        $escapeHtml = $this->getEscapeHtmlHelper();

        foreach ($options as $key => $optionSpec) {
            $value = '';
            $label = '';
            $selected = false;
            $disabled = false;

            if (is_scalar($optionSpec)) {
                $optionSpec = [
                    'label' => $optionSpec,
                    'value' => $key,
                ];
            }

            if (isset($optionSpec['options']) && is_array($optionSpec['options'])) {
                $optionStrings[] = $this->renderOptgroup($optionSpec, $selectedOptions);
                continue;
            }

            if (isset($optionSpec['value'])) {
                $value = $optionSpec['value'];
            }
            if (isset($optionSpec['label'])) {
                $label = $optionSpec['label'];
            }
            if (isset($optionSpec['selected'])) {
                $selected = $optionSpec['selected'];
            }
            if (isset($optionSpec['disabled'])) {
                $disabled = $optionSpec['disabled'];
            }

            $stringSelectedOptions = array_map('\\strval', $selectedOptions);
            if (ArrayUtils::inArray((string) $value, $stringSelectedOptions, true)) {
                $selected = true;
            }

            // Implementations of SelectSortTranslatedInterface skip translation
            // here because translations have already been made.
            if (!$implementsSortTranslated && null !== ($translator = $this->getTranslator())) {
                $label = $translator->translate(
                    $label,
                    $this->getTranslatorTextDomain()
                );
            }

            $attributes = [
                'value' => $value,
                'selected' => $selected,
                'disabled' => $disabled,
            ];

            if (isset($optionSpec['attributes']) && is_array($optionSpec['attributes'])) {
                $attributes = array_merge($attributes, $optionSpec['attributes']);
            }

            $this->validTagAttributes = $this->validOptionAttributes;
            $optionStrings[] = sprintf(
                $template,
                $this->createAttributesString($attributes),
                $escapeHtml($label)
            );
        }

        return implode("\n", $optionStrings);
    }

    /**
     * Sort options after translating them.
     */
    public function sortTranslated(array $options): array
    {
        $view = $this->getView();

        // Translate the labels.
        foreach ($options as &$option) {
            if (is_string($option)) {
                $option = $view->translate($option);
            } elseif (is_array($option)) {
                $option['label'] = $view->translate($option['label']);
            }
        }

        // Temporarily remove the empty option before sorting and finalizing.
        $emptyOption = null;
        if ('' === array_key_first($options)) {
            $emptyOption = array_shift($options);
        }

        // Get labels function.
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

        // Select elements may finalize the value options.
        $options = $this->element->finalizeValueOptions($options);

        // Reapply the empty option.
        if (null !== $emptyOption) {
            $options = ['' => $emptyOption] + $options;
        }

        return $options;
    }
}
