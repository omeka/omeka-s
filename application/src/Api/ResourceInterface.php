<?php
namespace Omeka\Api;

/**
 * API resource interface.
 */
interface ResourceInterface
{
    /**
     * Get the unique ID for this resource.
     */
    public function getId();
}
