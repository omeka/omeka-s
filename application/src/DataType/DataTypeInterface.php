<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ValueRepresentation;
use Zend\View\Renderer\PhpRenderer;

/**
 * Interface for data types.
 */
interface DataTypeInterface
{
    /**
     * Get the name of this data type.
     *
     * @return string
     */
    public function getName();

    /**
     * Get a human-readable label for this data type.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Prepare the view to enable the data type options.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareOptionsForm(PhpRenderer $view);

    /**
     * Get the data type options form.
     *
     * @param PhpRenderer $view
     * @return string|false
     */
    public function optionsForm(PhpRenderer $view);

    /**
     * Prepare the view to enable the data types.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareForm(PhpRenderer $view);

    /**
     * Get the template markup used to render the value in the resource form.
     *
     * @param PhpRenderer $view
     * @param AbstractResourceEntityRepresentation $resource
     * @param mixed $options
     * @return string
     */
    public function form(PhpRenderer $view, AbstractResourceEntityRepresentation $resource = null, $options = null);

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
     * @param AbstractEntityAdapter $adapter
     */
    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter);

    /**
     * Get the markup used to render the value.
     *
     * @param PhpRenderer $view
     * @param ValueRepresentation $value
     * @return string
     */
    public function render(PhpRenderer $view, ValueRepresentation $value);

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
