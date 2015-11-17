<?php
namespace Omeka\Settings;

use Omeka\Entity\Site;
use Omeka\Entity\SiteSetting as Setting;

class SiteSettings extends AbstractSettings
{
    /**
     * @var Site
     */
    protected $site;

    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    protected function setCache()
    {
        $settings = $this->getEntityManager()
            ->getRepository('Omeka\Entity\SiteSetting')->findBy(['site' => $this->site]);
        foreach ($settings as $setting) {
            $this->cache[$setting->getId()] = $setting->getValue();
        }
    }

    protected function setSetting($id, $value)
    {
        $setting = $this->getSetting($id);
        if ($setting instanceof Setting) {
            $setting->setValue($value);
        } else {
            $setting = new Setting;
            $setting->setId($id);
            $setting->setValue($value);
            $setting->setSite($this->site);
            $this->getEntityManager()->persist($setting);
        }
        $this->getEntityManager()->flush();
    }

    protected function deleteSetting($id)
    {
        $setting = $this->getSetting($id);
        if ($setting instanceof Setting) {
            $this->getEntityManager()->remove($setting);
            $this->getEntityManager()->flush();
        }
    }

    protected function getSetting($id)
    {
        return $this->getEntityManager()
            ->getRepository('Omeka\Entity\SiteSetting')
            ->findOneBy(['site' => $this->site, 'id' => $id]);
    }

}
