<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Stdlib\Message;
use Laminas\Form\Element;
use Laminas\View\Renderer\PhpRenderer;

class Fallback extends AbstractBlockLayout
{
    /**
     * @var string The name of the unknown block layout
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getLabel()
    {
        $message = new Message(
            'Unknown [%s]', //@translate
            $this->name
            );
        return $message;
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        // Preserve the original data.
        $element = new Element\Hidden("o:block[__blockIndex__][o:data]");
        $element->setValue(json_encode($block->data()));
        return $view->translate('This layout is invalid.') . $view->formElement($element);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }
}
