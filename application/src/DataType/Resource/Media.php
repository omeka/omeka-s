<?php
namespace Omeka\DataType\Resource;

use Omeka\DataType\ValueAnnotatingInterface;
use Omeka\Entity;
use Laminas\View\Renderer\PhpRenderer;

class Media extends AbstractResource implements ValueAnnotatingInterface
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
        return $view->partial('common/data-type/value-annotation-resource', [
            'dataTypeLabel' => $view->translate('Media'),
            'dataTypeSingle' => 'media',
            'dataTypePlural' => 'media',
        ]);
    }

    public function getValidValueResources()
    {
        return [Entity\Media::class];
    }
}
