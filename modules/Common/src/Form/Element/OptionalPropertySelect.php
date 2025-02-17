<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Form\Element\PropertySelect;

class OptionalPropertySelect extends PropertySelect
{
    use TraitOptionalElement;
}
