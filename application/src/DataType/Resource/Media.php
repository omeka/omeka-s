<?php
namespace Omeka\DataType\Resource;

use Omeka\DataType\ValueAnnotatableInterface;
use Laminas\View\Renderer\PhpRenderer;

class Media extends AbstractResource implements ValueAnnotatableInterface
{
    public function getName()
    {
        return 'resource:media';
    }

    public function getLabel()
    {
        return 'Media'; // @translate
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $view->partial('common/data-type/value-annotation-resource', ['dataTypeName' => $this->getName()]);
    }
}
