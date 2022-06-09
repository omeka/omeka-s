<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

interface ColumnTypeInterface
{
    /**
     * Get the label of this column type.
     */
    public function getLabel() : string;

    /**
     * Get the resource types that can use this column type.
     */
    public function getResourceTypes() : array;

    /**
     * Get the maximum amount of this column type for one category.
     */
    public function getMaxColumns() : ?int;

    /**
     * Render the data form of this column type.
     *
     * Form elements must have a "data-column-data-key" attribute with a value
     * that corresponds to the key in the column data array.
     */
    public function renderDataForm(PhpRenderer $view, array $data) : string;

    /**
     * Get the corresponding sort_by value of this column type.
     */
    public function getSortBy(array $data) : ?string;

    /**
     * Render the header of a column of this type.
     */
    public function renderHeader(PhpRenderer $view, array $data) : string;

    /**
     * Render the content of a column of this type.
     *
     * Return null to signal the use of a user-defined default.
     */
    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data) : ?string;
}
