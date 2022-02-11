<?php
namespace Omeka\Site\ResourcePageBlockLayout;

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
        return sprintf('Unknown [%s]', $this->name); // @translate
    }

    public function render(PhpRenderer $view)
    {
        return '';
    }
}
