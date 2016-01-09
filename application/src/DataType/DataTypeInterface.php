<?php
namespace Omeka\DataType;

use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for data types.
 */
interface DataTypeInterface
{
    /**
     * Get a human-readable label for this data type.
     *
     * @return string
     */
    public function getLabel();

    public function getTemplate(PhpRenderer $view);
}
