<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ResourcePageBlockLayoutInterface::class;

    protected $blockConfig;

    protected $themeManager;

    protected $siteSettings;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Unknown($name);
        }
        return $instance;
    }

    public function setBlockConfig(array $blockConfig)
    {
        $this->blockConfig = $blockConfig;
    }

    public function setThemeManager($themeManager)
    {
        $this->themeManager = $themeManager;
    }

    public function setSiteSettings($siteSettings)
    {
        $this->siteSettings = $siteSettings;
    }

    /**
     * Get all block layouts that are compatible with a resource.
     *
     * @param string $resourceName
     * @return array
     */
    public function getAllForResource($resourceName)
    {
        $allForResource = [];
        foreach ($this->getRegisteredNames() as $blockLayoutName) {
            $blockLayout = $this->get($blockLayoutName);
            $compatibleResourceNames = $blockLayout->getCompatibleResourceNames();
            if (in_array($resourceName, $compatibleResourceNames)) {
                $allForResource[$blockLayoutName] = $blockLayout;
            }
        }
        return $allForResource;
    }

    /**
     * Get all block layout labels.
     *
     * @return array
     */
    public function getAllLabels()
    {
        $allLabels = [];
        foreach ($this->getRegisteredNames() as $blockLayoutName) {
            $blockLayout = $this->get($blockLayoutName);
            $allLabels[$blockLayoutName] = $blockLayout->getLabel();
        }
        return $allLabels;
    }

    public function getBlockConfig($themeName)
    {
        $theme = $this->themeManager->getTheme($themeName);

        // Prioritize block config set by a site administrator.
        $resourcePageConfigSetting = $this->siteSettings->get($theme->getResourcePageConfigKey());
        $blockConfigSetting = $resourcePageConfigSetting['blocks'] ?? null;
        if ($blockConfigSetting) {
            return $this->standardizeBlockConfig($blockConfigSetting);
        }

        // If a site administrator did not configure blocks, use the block
        // config set in the theme configuration file.
        $resourcePageConfigTheme = $theme->getResourcePageConfig();
        $blockConfigTheme = $resourcePageConfigTheme['blocks'] ?? null;
        if (!$blockConfigTheme) {
            // Set fallback defaults if the theme has no block config.
            $blockConfigTheme = [
                'items' => ['main' => ['values', 'linkedResources']],
                'item_sets' => ['main' => ['values', 'linkedResources']],
                'media' => ['main' => ['values', 'linkedResources']],
            ];
        }

        // Merge the theme's block config with the block config set in module
        // configuration files.
        return array_merge_recursive(
            $this->standardizeBlockConfig($blockConfigTheme),
            $this->standardizeBlockConfig($this->blockConfig)
        );
    }

    public function standardizeBlockConfig($configIn)
    {
        $configIn = is_array($configIn) ? $configIn : [];
        $configOut = [];
        if (isset($configIn['items']['main']) && is_array($configIn['items']['main'])) {
            $configOut['items']['main'] = $configIn['items']['main'];
        } else {
            $configOut['items']['main'] = [];
        }
        if (isset($configIn['item_sets']['main']) && is_array($configIn['item_sets']['main'])) {
            $configOut['item_sets']['main'] = $configIn['item_sets']['main'];
        } else {
            $configOut['item_sets']['main'] = [];
        }
        if (isset($configIn['media']['main']) && is_array($configIn['media']['main'])) {
            $configOut['media']['main'] = $configIn['media']['main'];
        } else {
            $configOut['media']['main'] = [];
        }
        return $configOut;
    }
}
