<?php

namespace OmekaTest\Settings;

use Omeka\Test\DbTestCase;

class SiteSettingsTest extends DbTestCase
{
    protected $site1;
    protected $site2;

    public function setUp()
    {
        parent::setUp();

        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');

        // Trigger the creation of ModuleManager since it's the only place where
        // Omeka\Mvc\Status::setIsInstalled is called
        // (isInstalled is checked in Omeka\Settings\AbstractSettings)
        $moduleManager = $serviceLocator->get('Omeka\ModuleManager');

        $this->site1 = $api->create('sites', [
            'o:title' => 'Site 1',
            'o:slug' => 'site1',
            'o:theme' => 'default',
            'o:is_public' => 1,
        ])->getContent();
        $this->site2 = $api->create('sites', [
            'o:title' => 'Site 2',
            'o:slug' => 'site2',
            'o:theme' => 'default',
            'o:is_public' => 1,
        ])->getContent();
    }

    public function testCache()
    {
        $siteSettings = $this->getSiteSettings();

        $siteSettings->setTargetId(1);
        $siteSettings->set('site_title', $this->site1->title());
        $this->assertEquals($siteSettings->get('site_title'), $this->site1->title());
        $siteSettings->setTargetId(2);
        $siteSettings->set('site_title', $this->site2->title());
        $this->assertEquals($siteSettings->get('site_title'), $this->site2->title());

        $siteSettings->setTargetId(1);
        $this->assertEquals($siteSettings->get('site_title'), $this->site1->title());
    }

    protected function getServiceLocator()
    {
        return self::getApplication()->getServiceManager();
    }

    protected function getSiteSettings()
    {
        return $this->getServiceLocator()->get('Omeka\Settings\Site');
    }
}
