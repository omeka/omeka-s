<?php
namespace Omeka\Stdlib;

use JsonSerializable;

/**
 * A wrapper of PHP's DateTime that can be serialized as JSON and JSON-LD.
 *
 * When a null value is passed in constructor, the wrapper will wrap a null, so
 * its json-representation will be a null.
 * When no value is passed, the current date time is used.
 */
class DateTime implements JsonSerializable
{
    /**
     * @var \DateTime
     */
    protected $dateTime;

    /**
     * @param \DateTime|null $dateTime Null will set the current DateTime when
     * no arg is passed. When an arg is passed but its value is null, the json
     * serialization will be null.
     */
    public function __construct(?\DateTime $dateTime = null)
    {
        // Set current date and time when no arg is passed.
        $this->dateTime = func_num_args()
            ? $dateTime
            : new \DateTime();
    }

    /**
     * Get the underlying DateTime instance if any.
     */
    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }

    /**
     * Serialize DateTime as a JsonLd date time with w3c xml schema dateTime.
     *
     * Output a null if a null was passed in constructor.
     */
    public function getJsonLd(): ?array
    {
        return $this->dateTime
            ? [
                '@value' => $this->dateTime->format('c'),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ]
            : null;
    }

    /**
     * Serialize DateTime as an ISO 8601 date.
     *
     * Output a null if a null was passed in constructor.
     */
    public function jsonSerialize(): ?string
    {
        return $this->dateTime
            ? $this->dateTime->format('c')
            : null;
    }
}
