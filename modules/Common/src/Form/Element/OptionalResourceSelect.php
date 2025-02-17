<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Form\Element\ResourceSelect;

class OptionalResourceSelect extends ResourceSelect
{
    use TraitOptionalElement;
}
