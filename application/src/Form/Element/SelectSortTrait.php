<?php
namespace Omeka\Form\Element;

use Collator;

/**
 * The SelectSortInterface trait.
 *
 * Provides features for sorting and translating Select elements.
 *
 * @see Omeka\Form\Element\SelectSortInterface
 */
trait SelectSortTrait
{
    protected $compareFunction;

    public function sortValueOptions(): bool
    {
        return true;
    }

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
            if (extension_loaded('intl')) {
                $collator = new Collator('root');
                $this->compareFunction = function ($a, $b) use ($collator) {
                    return $collator->compare($a, $b);
                };
            } else {
                $this->compareFunction = function ($a, $b) {
                    return strcasecmp($a, $b);
                };
            }
        }
        return $this->compareFunction;
    }

    /**
     * Sort select options.
     */
    public function sortSelectOptions(array $options): array
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
