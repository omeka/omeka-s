<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Form\Element\SitePageSelect;

class OptionalSitePageSelect extends SitePageSelect
{
    use TraitOptionalElement;
    use TraitPrependAndGetValuesOptions;

    public function getValueOptions(): array
    {
        return $this->prependValuesOptions(parent::getValueOptions());
    }
}
