<?php
namespace Omeka\Stdlib;

use JsonSerializable;

/**
 * A wrapper of PHP's DateTime that can be serialized as JSON.
 */
class DateTime implements JsonSerializable
{
    /**
     * @var PhpDateTime
     */
    protected $dateTime;

    /**
     * @param \DateTime|null $dateTime Null will set the current DateTime
     */
    public function __construct(\DateTime $dateTime = null)
    {
        if (null === $dateTime) {
            // Set current date and time
            $dateTime = new \DateTime;
        }
        $this->dateTime = $dateTime;
    }

    /**
     * Get the underlying DateTime instance.
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Serialize DateTime as an ISO 8601 date.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->dateTime->format('c');
    }
}
