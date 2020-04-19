<?php
namespace Omeka\DataType;

use Omeka\Api\Representation\ValueRepresentation;
use Laminas\View\Renderer\PhpRenderer;

abstract class AbstractDataType implements DataTypeInterface
{
    public function getOptgroupLabel()
    {
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function toString(ValueRepresentation $value)
    {
        return (string) $value->value();
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return $value->value();
    }
}
