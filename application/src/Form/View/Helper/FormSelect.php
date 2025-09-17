<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormSelect as LaminasFormSelect;
use Laminas\Stdlib\ArrayUtils;
use Omeka\Form\Element\SelectSortInterface;
use Omeka\Form\Element\SelectSortTrait;

class FormSelect extends LaminasFormSelect
{
    use SelectSortTrait;

    protected $element;

    protected $valueOptionsFinalized;

    /**
     * Override of parent::render().
     */
    public function render(ElementInterface $element): string
    {
        if ($element instanceof SelectSortInterface) {
            // Temporarily set variables related to SelectSortInterface so
            // downstream code can detect whether to sort value options after
            // translating.
            $this->element = $element;
            $this->valueOptionsFinalized = false;
        }

        $rendered = parent::render($element);

        if ($element instanceof SelectSortInterface) {
            // Reset variables related to SelectSortInterface.
            $this->element = null;
            $this->valueOptionsFinalized = null;
        }

        return $rendered;
    }

    /**
     * Override and reimplementation of parent::renderOptions().
     */
    public function renderOptions(array $options, array $selectedOptions = []): string
    {
        if ($this->implementsSortTranslated()) {
            // Implementations of SelectSortInterface override default behavior
            // by sorting value options *after* they are translated. Normally
            // they are sorted before they are translated.
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

            if (!$this->implementsSortTranslated() && null !== ($translator = $this->getTranslator())) {
                // Implementations of SelectSortInterface skip translations here
                // because translations have already been made.
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
     * Does the select element implement SelectSortInterface?
     */
    public function implementsSortTranslated(): bool
    {
        return (
            isset($this->element)
            && ($this->element instanceof SelectSortInterface)
        );
    }

    /**
     * Sort options after translating them.
     */
    public function sortTranslated(array $options): array
    {
        if (!$this->implementsSortTranslated()) {
            return $options;
        }

        $view = $this->getView();

        // Temporarily remove the empty option before sorting and finalizing.
        $emptyOption = null;
        if ('' === array_key_first($options)) {
            $emptyOption = $view->translate(array_shift($options));
        }

        // Translate the labels.
        if ($this->element->translateValueOptions()) {
            foreach ($options as &$option) {
                if (is_string($option)) {
                    $option = $view->translate($option);
                } elseif (is_array($option)) {
                    $option['label'] = $view->translate($option['label']);
                }
            }
        }

        // Get labels function.
        $getLabel = function ($option) {
            if (is_string($option)) {
                return $option;
            } elseif (is_array($option)) {
                return $option['label'];
            }
        };
        $compare = $this->getCompareFunction();
        // Sort the options alphabetically.
        uasort($options, function ($a, $b) use ($getLabel, $compare) {
            return $compare($getLabel($a), $getLabel($b));
        });

        // Select elements may finalize the value options. Prevent calling
        // finalizeValueOptions more than once.
        if (false === $this->valueOptionsFinalized) {
            $this->valueOptionsFinalized = true;
            $options = $this->element->finalizeValueOptions($options);
        }

        // Reapply the empty option.
        if (null !== $emptyOption) {
            $options = ['' => $emptyOption] + $options;
        }

        return $options;
    }
}
