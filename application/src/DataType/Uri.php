<?php
namespace Omeka\DataType;

use Zend\View\Renderer\PhpRenderer;

class Uri extends AbstractDataType
{
    public function getLabel()
    {
        return 'URI';
    }

    public function getTemplate(PhpRenderer $view)
    {
        return $view->partial('common/data-type/uri');
    }
}
