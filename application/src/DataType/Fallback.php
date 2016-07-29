<?php
namespace Omeka\DataType;

class Fallback extends Literal
{
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLabel()
    {
        return sprintf('%s [%s]', 'Unknown', $this->name); // @translate
    }
}
