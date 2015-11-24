<?php
namespace Omeka\Settings;

use Omeka\Entity\Setting;

class Settings extends AbstractSettings
{
    protected function setCache()
    {
        $settings = $this->getConnection()->fetchAll('SELECT * FROM setting');
        foreach ($settings as $setting) {
            $this->cache[$setting['id']] = json_decode($setting['value']);
        }
    }

    protected function setSetting($id, $value)
    {
        $conn = $this->getConnection();
        $setting = $conn->fetchAssoc('SELECT * FROM setting WHERE id = ?', [$id]);
        if ($setting) {
            $conn->update('setting', ['value' => json_encode($value)], ['id' => $id]);
        } else {
            $conn->insert('setting', ['id' => $id, 'value' => json_encode($value)]);
        }
    }

    protected function deleteSetting($id)
    {
        $this->getConnection()->delete('setting', ['id' => $id]);
    }
}
