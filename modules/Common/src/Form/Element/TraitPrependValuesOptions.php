<?php declare(strict_types=1);

namespace Common\Form\Element;

trait TraitPrependValuesOptions
{
    /**
     * Prepend configured value options.
     */
    protected function prependValuesOptions(array $valueOptions): array
    {
        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        return $valueOptions;
    }
}
