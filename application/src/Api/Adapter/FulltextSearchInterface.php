<?php
namespace Omeka\Api\Adapter;

interface FulltextSearchInterface
{
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
