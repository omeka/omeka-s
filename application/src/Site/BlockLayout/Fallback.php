<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Stdlib\Message;
use Zend\View\Renderer\PhpRenderer;

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

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        $message = new Message(
            'Unknown [%s]', //@translate
            $this->name
            );
        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        return $view->translate('This layout is invalid.');
    }

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }
}
