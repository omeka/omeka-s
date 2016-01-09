<?php
namespace Omeka\DataType;

use Zend\View\Renderer\PhpRenderer;

class Resource extends AbstractDataType
{
    public function getLabel()
    {
        return 'Resource';
    }

    public function getTemplate(PhpRenderer $view)
    {
        return $view->partial('common/data-type/resource');
    }
}
