<?php
namespace Omeka\Settings;

use Omeka\Entity\Site;
use Omeka\Entity\SiteSetting as Setting;
use Omeka\Service\Exception;

class SiteSettings extends AbstractSettings
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * Set the site entity from which to get settings.
     *
     * @param Site $site
     */
    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    protected function setCache()
    {
        if (!$this->site instanceof Site) {
            throw new Exception\RuntimeException('Cannot use site settings when no site is set');
        }
        $conn = $this->getConnection();
        $settings = $conn->fetchAll('SELECT * FROM site_setting');
        foreach ($settings as $setting) {
            $this->cache[$setting['id']] = $conn->convertToPHPValue($setting['value'], 'json_array');
        }
    }

    protected function setSetting($id, $value)
    {
        $conn = $this->getConnection();
        $setting = $conn->fetchAssoc(
            'SELECT * FROM site_setting WHERE id = ? AND site_id = ?',
            [$id, $this->site->getId()]
        );
        if ($setting) {
            $conn->update('site_setting', ['value' => $value], ['id' => $id], ['json_array']);
        } else {
            $conn->insert('site_setting', [
                'id' => $id,
                'site_id' => $this->site->getId(),
                'value' => $value,
            ], [\PDO::PARAM_STR, \PDO::PARAM_INT, 'json_array']);
        }
    }

    protected function deleteSetting($id)
    {
        $this->getConnection()->delete('setting', [
            'id' => $id,
            'site_id' => $this->site->getId(),
        ], [\PDO::PARAM_STR, \PDO::PARAM_INT]);
    }
}
