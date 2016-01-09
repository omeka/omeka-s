<?php
namespace Omeka\DataType;

use Zend\View\Renderer\PhpRenderer;

class Literal extends AbstractDataType
{
    public function getLabel()
    {
        return 'Literal';
    }

    public function getTemplate(PhpRenderer $view)
    {
        return $view->partial('common/data-type/literal');
    }
}
