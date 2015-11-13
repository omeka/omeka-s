<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
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
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return sprintf('%s [%s]', $translator->translate('Unknown'), $this->name);
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageBlockRepresentation $block = null
    ) {
        return 'This layout is invalid.';
    }

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return '';
    }
}
