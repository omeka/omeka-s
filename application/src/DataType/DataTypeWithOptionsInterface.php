<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;
use Omeka\Api\Representation\ValueRepresentation;
use Laminas\View\Renderer\PhpRenderer;

/**
 * Interface for data types that accept render options.
 */
interface DataTypeWithOptionsInterface extends DataTypeInterface
{
    /**
     * Get the markup used to render the value.
     *
     * @param PhpRenderer $view
     * @param ValueRepresentation $value
     * @param array $options A common option is "lang".
     * @return string
     */
    public function render(PhpRenderer $view, ValueRepresentation $value, $options = []);
}
