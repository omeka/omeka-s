<?php
namespace Omeka\DataType;

use Laminas\View\Renderer\PhpRenderer;

interface ValueAnnotatableInterface
{
    public function valueAnnotationPrepareForm(PhpRenderer $view);

    public function valueAnnotationForm(PhpRenderer $view);
}
