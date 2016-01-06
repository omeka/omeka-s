<?php
namespace Omeka\DateType;

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
}
