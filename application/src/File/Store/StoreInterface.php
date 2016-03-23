<?php
namespace Omeka\File\Store;

/**
 * Interface for a store of files.
 *
 * File stores abstract over simple file operations.
 */
interface StoreInterface
{
    /**
     * Store a file.
     *
     * @param string $source Local path to the file to store
     * @param string $storagePath Storage path to store at
     */
    public function put($source, $storagePath);

    /**
     * Delete a stored file.
     *
     * @param string $storagePath Storage path for file
     */
    public function delete($storagePath);

    /**
     * Get the URI for a stored file.
     *
     * @param string $storagePath Storage path for file
     */
    public function getUri($storagePath);
}
