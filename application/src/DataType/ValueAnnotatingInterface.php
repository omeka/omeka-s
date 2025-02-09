<?php
namespace Omeka\DataType;

use Laminas\View\Renderer\PhpRenderer;

/**
 * Value annotating data type
 *
 * For a data type to be value annotating, it must implement this interface and
 * register the data type in configuration under [data_types][value_annotating].
 */
interface ValueAnnotatingInterface
{
    /**
     * Prepare the view to enable the data type annotation.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function valueAnnotationPrepareForm(PhpRenderer $view);

    /**
     * Get the template markup used to render the value annotation in the
     * resource form.
     *
     * @param PhpRenderer $view
     * @return string
     */
    public function valueAnnotationForm(PhpRenderer $view);
}
