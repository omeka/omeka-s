<?php
namespace Omeka\Form\Element;

use Collator;

trait SelectSortTrait
{
    protected $compareFunction;

    public function translateValueOptions(): bool
    {
        return true;
    }

    public function finalizeValueOptions(array $options): array
    {
        return $options;
    }

    /**
     * Get the compare function to use when sorting options.
     */
    public function getCompareFunction(): callable
    {
        if (!isset($this->compareFunction)) {
            $collator = extension_loaded('intl') ? new Collator('root') : false;
            $this->compareFunction = function ($a, $b) use ($collator) {
                return $collator ? $collator->compare($a, $b) : strcasecmp($a, $b);
            };
        }
        return $this->compareFunction;
    }

    /**
     * Sort selector options.
     */
    public function sortSelectorOptions(array $options): array
    {
        $getLabel = function ($option) {
            if (is_string($option)) {
                return $option;
            } elseif (is_array($option)) {
                return $option['label'];
            }
        };
        $compare = $this->getCompareFunction();
        uasort($options, function ($a, $b) use ($compare, $getLabel) {
            return $compare($getLabel($a), $getLabel($b));
        });
        foreach ($options as &$option) {
            uasort($option['options'], function ($a, $b) use ($compare, $getLabel) {
                return $compare($getLabel($a), $getLabel($b));
            });
        }
        return $options;
    }

}
