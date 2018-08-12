<?php
namespace Omeka\Api;

/**
 * A registered API resource
 */
class Resource implements ResourceInterface
{
    /**
     * @var string
     */
    protected $resourceId;

    /**
     * @param string $resourceId
     */
    public function __construct($resourceId)
    {
        $this->resourceId = $resourceId;
    }

    public function getId()
    {
        return $this->resourceId;
    }
}
