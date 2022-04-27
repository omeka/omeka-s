<?php
namespace Omeka\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

interface ColumnTypeInterface
{
    /**
     * Get the label of this column type.
     *
     * @return string
     */
    public function getLabel() : string;

    /**
     * Get the resource types that can use this column type.
     *
     * @return array
     */
    public function getResourceTypes() : array;

    /**
     * Get the maximum amount of this column type for one category.
     *
     * @return ?int
     */
    public function getMaxColumns() : ?int;

    /**
     * Prepare the data form of this column type.
     *
     * @param PhpRenderer $view
     */
    public function prepareDataForm(PhpRenderer $view) : void;

    /**
     * Render the data form of this column type.
     *
     * @param PhpRenderer $view
     * @param array $data
     * @return string
     */
    public function renderDataForm(PhpRenderer $view, array $data) : string;

    /**
     * Get the corresponding sort_by value of this column type.
     *
     * @return ?string
     */
    public function getSortBy(array $data) : ?string;

    /**
     * Render the header of a column of this type.
     *
     * @param PhpRenderer $view
     * @param array $data
     * @return string
     */
    public function renderHeader(PhpRenderer $view, array $data) : string;

    /**
     * Render the content of a column of this type.
     *
     * @param PhpRenderer $view
     * @param AbstractResourceEntityRepresentation $resource
     * @param array $data
     * @return ?string
     */
    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string;
}
