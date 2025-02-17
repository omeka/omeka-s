<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Form\Element\SiteSelect;

class OptionalSiteSelect extends SiteSelect
{
    use TraitOptionalElement;
    use TraitPrependAndGetValuesOptions;

    public function getValueOptions(): array
    {
        return $this->prependValuesOptions(parent::getValueOptions());
    }
}
