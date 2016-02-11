<?php
namespace Omeka\Settings;

class Settings extends AbstractSettings
{
    protected function setCache()
    {
        $conn = $this->getConnection();
        $settings = $conn->fetchAll('SELECT * FROM setting');
        foreach ($settings as $setting) {
            $this->cache[$setting['id']] = $conn->convertToPHPValue($setting['value'], 'json_array');
        }
    }

    protected function setSetting($id, $value)
    {
        $conn = $this->getConnection();
        $setting = $conn->fetchAssoc('SELECT * FROM setting WHERE id = ?', [$id]);
        if ($setting) {
            $conn->update('setting', ['value' => $value], ['id' => $id], ['json_array']);
        } else {
            $conn->insert('setting', ['value' => $value, 'id' => $id], ['json_array']);
        }
    }

    protected function deleteSetting($id)
    {
        $this->getConnection()->delete('setting', ['id' => $id]);
    }
}
