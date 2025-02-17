<?php declare(strict_types=1);

namespace Common\Form\Element;

trait TraitPrependAndGetValuesOptions
{
    use TraitPrependValuesOptions;

    public function getValueOptions(): array
    {
        return $this->prependValuesOptions($this->valueOptions);
    }
}
