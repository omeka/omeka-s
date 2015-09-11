<?php
namespace Omeka\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Hidden;
use Zend\View\Renderer\PhpRenderer;

class OneItem extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'One Item';
    }

    public function prepareForm(PhpRenderer $view)
    {
        $script = '
$(document).ready(function() {
    $("body").on("click", ".item-select", function(e) {
        e.preventDefault();
        var context = $(this);
        $(".selecting-attachment").removeClass("selecting-attachment");
        context.parents(".block-attachment").addClass("selecting-attachment");
        Omeka.openSidebar(context, "#select-resource");
    });
    $("#select-item a").on("o:resource-selected", function(e) {
        var resource = $(".resource-details").data("resource-values");
        console.log(resource);
        var html = "<h3>" + resource.display_title + "</h3>" +
            "<img src=\'" + resource.thumbnail_url + "\'></a>";
        var selecting = $(".block-attachment.selecting-attachment");
        selecting.html(html);
        selecting.siblings("input").val(resource.value_resource_id);
    });
});';
        $view->headscript()->appendScript($script);
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {}

    public function form(PhpRenderer $view, $index, SitePageBlockRepresentation $block = null)
    {
        $hidden = new Hidden("o:block[$index][o:attachment][][o:item][o:id]");
        if ($block) {
            $id = $block->attachments()[0]->item()->id();
            $hidden->setAttribute('value', $id);
            try {
                $response = $this->getServiceLocator()
                    ->get('Omeka\ApiManager')
                    ->read('items', $id);
                $item = $response->getContent();
                $content = sprintf(
                    '<h3>%s</h3><img src="%s">',
                    $item->displayTitle(),
                    $item->primaryMedia()->thumbnailUrl('square')
                );
            } catch (\Exception $e) {
                $content = '[previously attached item is now invalid]';
            }
        } else {
            $sidebarContentUrl = $view->url(
                'admin/default',
                array(
                    'controller' => 'item',
                    'action' => 'sidebar-select'
                ),
                false
            );
            $content = sprintf(
                '<button class="item-select" data-sidebar-content-url="%s">Select Item</button>',
                $view->escapeHtml($sidebarContentUrl)
            );
        }
        $html = '
<div class="field">
    <div class="field-meta">
        <label>Item</label>
    </div>
    <div class="inputs">
        <div class="block-attachment">
            ' . $content . '
        </div>
        ' . $view->formField($hidden) . '
    </div>
</div>';
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {}
}
