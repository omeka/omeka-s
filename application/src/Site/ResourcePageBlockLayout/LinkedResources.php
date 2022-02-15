<?php
namespace Omeka\Site\ResourcePageBlockLayout;

class LinkedResources implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Linked resources'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'media', 'item_sets'];
    }

    public function render(PhpRenderer $view) : string
    {}
}
