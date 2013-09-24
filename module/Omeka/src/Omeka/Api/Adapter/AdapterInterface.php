<?php
namespace Omeka\Api\Adapter;

/**
 * API adapter interface.
 */
interface AdapterInterface
{
    public function search();
    public function create();
    public function read($id);
    public function update($id);
    public function delete($id);
    public function setData(array $data);
    public function getData($key = null);
}
