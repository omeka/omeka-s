<?php
namespace Omeka\Stdlib;

use Collator;

trait SelectSortTrait
{
    protected $compareFunction;

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
        $compare = $this->getCompareFunction();
        uasort($options, function ($a, $b) use ($compare) {
            return $compare($a['label'], $b['label']);
        });
        foreach ($options as &$option) {
            uasort($option['options'], function ($a, $b) use ($compare) {
                return $compare($a['label'], $b['label']);
            });
        }
        return $options;
    }
}
