<?php
namespace Omeka\Settings;

interface SettingsInterface
{
    /**
     * Set a setting.
     *
     * @param string $id
     * @param string $value
     */
    public function set($id, $value);

    /**
     * Get a setting
     *
     * @param string $id
     * @return mixed
     */
    public function get($id, $default = null);

    /**
     * Delete a setting
     *
     * @param string $id
     */
    public function delete($id);
}
