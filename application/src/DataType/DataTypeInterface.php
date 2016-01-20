<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;
use Omeka\Api\Representation\ValueRepresentation;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for data types.
 */
interface DataTypeInterface
{
    /**
     * Get a human-readable label for this data type.
     *
     * @param string $dataType The data type name (used for dynamic data types)
     * @return string
     */
    public function getLabel($dataType);

    /**
     * Prepare the view to enable the data types.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareForm(PhpRenderer $view, $dataType);

    /**
     * Get the template markup used to render the value in the resource form.
     *
     * @param PhpRenderer $view
     * @param string $dataType The data type name (used for dynamic data types)
     * @return string
     */
    public function getTemplate(PhpRenderer $view, $dataType);

    /**
     * Is this value object valid?
     *
     * @param array $valueObject
     * @return bool
     */
    public function isValid(array $valueObject);

    /**
     * Hydrate the value entity using the value object.
     *
     * @param array $valueObject
     * @param Value $value
     */
    public function hydrate(array $valueObject, Value $value);

    /**
     * Get the markup used to render the value.
     *
     * @param PhpRenderer $view
     * @param ValueRepresentation $value
     * @return string
     */
    public function getHtml(PhpRenderer $view, ValueRepresentation $value);

    /**
     * Get the value as a simple string.
     *
     * @param ValueRepresentation $value
     * @return string
     */
    public function toString(ValueRepresentation $value);

    /**
     * Get an array representation of this value using JSON-LD notation.
     *
     * @param ValueRepresentation $value
     * @return array
     */
    public function getJsonLd(ValueRepresentation $value);
}
