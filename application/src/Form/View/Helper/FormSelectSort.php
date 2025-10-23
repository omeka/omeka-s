<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\Exception;
use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormSelect as LaminasFormSelect;
use Laminas\Stdlib\ArrayUtils;
use Omeka\Form\Element\SelectSortInterface;
use Omeka\Form\Element\SelectSortTrait;

class FormSelectSort extends LaminasFormSelect
{
    use SelectSortTrait;

    protected $element;

    protected $valueOptionsFinalized;

    /**
     * Override of parent::render().
     */
    public function render(ElementInterface $element): string
    {
        if (!$element instanceof SelectSortInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the element implements Omeka\Form\Element\SelectSortInterface',
                __METHOD__
            ));
        }

        // Temporarily set variables related to SelectSortInterface so downstream
        // code can detect whether to translate and sort value options.
        $this->element = $element;
        $this->valueOptionsFinalized = false;

        $rendered = parent::render($element);

        // Reset variables related to SelectSortInterface.
        $this->element = null;
        $this->valueOptionsFinalized = null;

        return $rendered;
    }

    /**
     * Override and reimplementation of parent::renderOptions().
     */
    public function renderOptions(array $options, array $selectedOptions = []): string
    {
        // Implementations of SelectSortInterface override default behavior by
        // sorting value options *after* they are translated. Normally they are
        // sorted before they are translated.
        $options = $this->sortTranslated($options);

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

            // Note that the parent FormSelect would translate labels here, but
            // we skip them in FormSelectSort because translations have already
            // been made in self::sortTranslated().

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

        // Temporarily remove the empty option before sorting and finalizing.
        $emptyOption = null;
        if ('' === array_key_first($options)) {
            $emptyOption = $view->translate(array_shift($options));
        }

        if ($this->element->translateValueOptions()) {
            // Translate the labels.
            foreach ($options as &$option) {
                if (is_string($option)) {
                    $option = $view->translate($option);
                } elseif (is_array($option)) {
                    $option['label'] = $view->translate($option['label']);
                }
            }
        }

        if ($this->element->sortValueOptions()) {
            // Sort the labels.
            $getLabel = function ($option) {
                if (is_string($option)) {
                    return $option;
                } elseif (is_array($option)) {
                    return $option['label'];
                }
            };
            $compare = $this->getCompareFunction();
            uasort($options, function ($a, $b) use ($getLabel, $compare) {
                return $compare($getLabel($a), $getLabel($b));
            });
        }

        // Select elements may finalize the value options. Must prevent calling
        // finalizeValueOptions more than once because this method is recursive.
        if (false === $this->valueOptionsFinalized) {
            $this->valueOptionsFinalized = true;
            $options = $this->element->finalizeValueOptions($options);
        }

        // Re-apply the empty option.
        if (null !== $emptyOption) {
            $options = ['' => $emptyOption] + $options;
        }

        return $options;
    }
}
