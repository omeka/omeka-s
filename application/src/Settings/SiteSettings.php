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
        $settings = $conn->fetchAll('SELECT * FROM site_setting WHERE site_id = ?', [$this->site->getId()]);
        foreach ($settings as $setting) {
            $this->cache[$setting['id']] = $conn->convertToPHPValue($setting['value'], 'json_array');
        }
    }

    protected function setSetting($id, $value)
    {
        $conn = $this->getConnection();
        $siteId = $this->site->getId();
        $setting = $conn->fetchAssoc(
            'SELECT * FROM site_setting WHERE id = ? AND site_id = ?',
            [$id, $siteId]
        );
        if ($setting) {
            $conn->update('site_setting', ['value' => $value],
                ['id' => $id, 'site_id' => $siteId], ['json_array']);
        } else {
            $conn->insert('site_setting', [
                'value' => $value,
                'site_id' => $siteId,
                'id' => $id,
            ], ['json_array', \PDO::PARAM_INT]);
        }
    }

    protected function deleteSetting($id)
    {
        $this->getConnection()->delete('site_setting', [
            'site_id' => $this->site->getId(),
            'id' => $id,
        ], [\PDO::PARAM_INT]);
    }
}
