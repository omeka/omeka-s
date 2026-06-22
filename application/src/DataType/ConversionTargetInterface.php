<?php
namespace Omeka\DataType;

use Omeka\Entity\Value;

interface ConversionTargetInterface
{
    /**
     * Convert a value to the target data type.
     *
     * Return true if the conversion was succesfully done. Return false if the
     * conversion was not possible. Conversions should avoid data loss by not
     * overwriting existing data. The value hydrator will log when conversions
     * are not possible, and it will change the data type for you, so there's no
     * need to do it here.
     *
     * @param Value $valueObject
     * @param string $dataTypeTarget Only needed for extreme edge cases where
     *     the data type does not know its own name.
     * @return bool
     */
    public function convert(Value $valueObject, string $dataTypeTarget): bool;
}
