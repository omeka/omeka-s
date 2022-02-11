<?php
namespace Omeka\Site\ResourcePageBlockLayout;

class Values implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Values'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'media', 'item_sets'];
    }

    public function render(PhpRenderer $view) : string
    {}
}
