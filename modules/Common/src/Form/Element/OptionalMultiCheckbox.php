<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\MultiCheckbox;

class OptionalMultiCheckbox extends MultiCheckbox
{
    use TraitOptionalElement;
}
