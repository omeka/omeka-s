<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\Date;

class OptionalDate extends Date
{
    use TraitOptionalElement;
}
