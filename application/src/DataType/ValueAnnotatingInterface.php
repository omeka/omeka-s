<?php
namespace Omeka\DataType;

use Laminas\View\Renderer\PhpRenderer;

interface ValueAnnotatingInterface
{
    public function valueAnnotationPrepareForm(PhpRenderer $view);

    public function valueAnnotationForm(PhpRenderer $view);
}
