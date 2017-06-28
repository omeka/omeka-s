<?php
namespace Omeka\DataType;

use Omeka\Api\Representation\ValueRepresentation;
use Zend\View\Renderer\PhpRenderer;

abstract class AbstractDataType implements DataTypeInterface
{
    public function prepareOptionsForm(PhpRenderer $view)
    {
    }

    public function optionsForm(PhpRenderer $view)
    {
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function toString(ValueRepresentation $value)
    {
        return (string) $value->value();
    }
}
