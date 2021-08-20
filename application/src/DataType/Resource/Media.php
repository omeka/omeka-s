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
        $form = '
        <span class="display-title"></span>
        <input type="hidden" class="value_resource_id" data-value-key="value_resource_id">
        <input type="hidden" class="display_title" data-value-key="display_title">
        %s';
        return sprintf(
            $form,
            $view->hyperlink($view->translate('Media'), '#', [
                'class' => 'o-icon-media button value-annotation-resource-select',
                'data-sidebar-content-url' => $view->url('admin/default', ['controller' => 'media', 'action' => 'sidebar-select'], false),
            ])
        );
    }
}
