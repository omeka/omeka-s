<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\Email;

class OptionalEmail extends Email
{
    use TraitOptionalElement;
}
