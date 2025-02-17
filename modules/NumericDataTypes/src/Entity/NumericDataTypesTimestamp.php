<?php
namespace NumericDataTypes\Entity;

/**
 * @Entity
 */
class NumericDataTypesTimestamp extends NumericDataTypesNumber
{
    /**
     * @Column(type="bigint")
     */
    protected $value;

    public function setValue($value)
    {
        $this->value = (int) $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
