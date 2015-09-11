<?php
namespace Omeka\BlockLayout;

use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Textarea;
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
        var html = "<h4>" + resource.display_title + "</h4>";
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
        $caption = new Textarea("o:block[$index][o:attachment][0][o:caption]");
        $hidden = new Hidden("o:block[$index][o:attachment][0][o:item][o:id]");
        if ($block) {
            $attachment = $block->attachments()[0];
            $hidden->setAttribute('value', $attachment->item()->id());
            $caption->setAttribute('value', $attachment->caption());
            $content = sprintf('<h4>%s</h4>',$attachment->item()->displayTitle());
        } else {
            $sidebarContentUrl = $view->url('admin/default', array(
                'controller' => 'item',
                'action' => 'sidebar-select',
            ));
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
        <div class="block-attachment">' . $content . '</div>
        ' . $view->formField($hidden) . '
    </div>
</div>
<div class="field">
    <div class="field-meta">
        <label>Caption</label>
    </div>
    <div class="inputs">
        ' . $view->formField($caption) . '
    </div>
</div>';
        return $html;
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {}
}
