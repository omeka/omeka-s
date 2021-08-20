<?php
namespace Omeka\DataType\Resource;

use Omeka\DataType\ValueAnnotatableInterface;
use Laminas\View\Renderer\PhpRenderer;

class Item extends AbstractResource implements ValueAnnotatableInterface
{
    public function getName()
    {
        return 'resource:item';
    }

    public function getLabel()
    {
        return 'Item'; // @translate
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
            $view->hyperlink($view->translate('Items'), '#', [
                'class' => 'o-icon-items button value-annotation-resource-select',
                'data-sidebar-content-url' => $view->url('admin/default', ['controller' => 'item', 'action' => 'sidebar-select'], false),
            ])
        );
    }
}
