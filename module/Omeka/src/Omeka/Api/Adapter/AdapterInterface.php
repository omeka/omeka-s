<?php
namespace Omeka\Api\Adapter;

/**
 * API adapter interface.
 */
interface AdapterInterface
{
    /**
     * Search a set of resources.
     *
     * @param mixed $data
     * @return mixed
     */
    public function search($data = null);

    /**
     * Create a resource.
     *
     * @param mixed $data
     * @return mixed
     */
    public function create($data = null);

    /**
     * Read a resource.
     *
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function read($id, $data = null);

    /**
     * Update a resource.
     *
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function update($id, $data = null);

    /**
     * Delete a resource.
     *
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function delete($id, $data = null);
}
