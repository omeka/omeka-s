<?php
namespace Omeka\Settings;

use Omeka\Entity\Setting;

class Settings extends AbstractSettings
{
    protected function setCache()
    {
        $settings = $this->getEntityManager()
            ->getRepository('Omeka\Entity\Setting')->findAll();
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
            ->getRepository('Omeka\Entity\Setting')
            ->findOneById($id);
    }
}
