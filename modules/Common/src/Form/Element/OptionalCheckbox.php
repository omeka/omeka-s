<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\Checkbox;

class OptionalCheckbox extends Checkbox
{
    use TraitOptionalElement;
}
