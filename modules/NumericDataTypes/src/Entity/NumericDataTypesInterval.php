<?php
namespace NumericDataTypes\Entity;

/**
 * @Entity
 */
class NumericDataTypesInterval extends NumericDataTypesNumber
{
    /**
     * @Column(type="bigint")
     */
    protected $value;

    /**
     * @Column(type="bigint")
     */
    protected $value2;

    public function setValue($value)
    {
        $this->value = (int) $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue2($value2)
    {
        $this->value2 = (int) $value2;
    }

    public function getValue2()
    {
        return $this->value2;
    }
}
