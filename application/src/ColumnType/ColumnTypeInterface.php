<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

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
     * Is this data valid for this column type?
     */
    public function dataIsValid(array $data) : bool;

    /**
     * Prepare the data form of this column type.
     */
    public function prepareDataForm(PhpRenderer $view) : void;

    /**
     * Render the data form of this column type.
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
     */
    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string;
}
