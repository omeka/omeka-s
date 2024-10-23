<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;

/**
 * Interface for converting from one data type to another.
 */
interface ConvertableInterface
{
    /**
     * Convert the data type.
     *
     * This method is responsible for making all changes to the Value object
     * that are needed to convert from one data type to this one. Do nothing if
     * the conversion is not possible. Remember to set the value's new type
     * using Value::setType();
     *
     * @param Value $valueObject
     * @param string $dataTypeName
     */
    public function convert(Value $valueObject, string $dataTypeName);
}
