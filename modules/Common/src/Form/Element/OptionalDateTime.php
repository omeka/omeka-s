<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\DateTime;

class OptionalDateTime extends DateTime
{
    use TraitOptionalElement;
}
