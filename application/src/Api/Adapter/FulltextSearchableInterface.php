<?php
namespace Omeka\Api\Adapter;

interface FulltextSearchableInterface
{
    /**
     * Get the owner of the passed resource.
     *
     * @param mixed $resource
     * @return Omeka\Entity\User
     */
    public function getFulltextOwner($resource);

    /**
     * Is the passed resource public?
     *
     * @param mixed $resource
     * @param return bool
     */
    public function getFulltextIsPublic($resource);

    /**
     * Get the title of the passed resource.
     *
     * @param mixed $resource
     * @return string
     */
    public function getFulltextTitle($resource);

    /**
     * Get the the text of the passed resource.
     *
     * @param mixed $resource
     * @return string
     */
    public function getFulltextText($resource);
}
