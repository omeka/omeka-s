<?php
namespace Omeka\DataType;

use Zend\View\Renderer\PhpRenderer;

class Normal extends AbstractDataType
{
    public function getLabel()
    {
        return 'Normal';
    }

    public function getTemplate(PhpRenderer $view)
    {
        return $view->partial('common/data-type/normal');
    }
}
