<?php
namespace Omeka\Stdlib;

use Zend\Stdlib\JsonSerializable;

class DateTime implements JsonSerializable
{
    /**
     * @var PhpDateTime
     */
    protected $dateTime;

    /**
     * @param PhpDateTime $dateTime
     */
    public function __construct(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Get the underlying DateTime instance.
     *
     * @return PhpDateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->dateTime->format('c');
    }
}
