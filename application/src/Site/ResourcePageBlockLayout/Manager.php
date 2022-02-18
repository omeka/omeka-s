<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\ServiceManager\AbstractPluginManager;
use Omeka\Site\Theme\Theme;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ResourcePageBlockLayoutInterface::class;

    protected $resourcePageBlocks;

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

    public function setResourcePageBlocks(array $resourcePageBlocks)
    {
        $this->resourcePageBlocks = $resourcePageBlocks;
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

    /**
     * Get the current resource page blocks configuration for a theme.
     *
     * @param Theme $theme
     * @return array
     */
    public function getResourcePageBlocks(Theme $theme)
    {
        // Prioritize blocks set by a site administrator.
        $themeSettings = $this->siteSettings->get($theme->getSettingsKey());
        $resourcePageBlocks = $themeSettings['resource_page_blocks'] ?? null;
        if ($resourcePageBlocks) {
            return $this->standardizeResourcePageBlocks($resourcePageBlocks);
        }

        // If a site administrator did not set any blocks, use the theme's
        // blocks configuration (set in the theme's INI file), if any.
        $themeConfig = $theme->getConfigSpec();
        $resourcePageBlocks = $themeConfig['resource_page_blocks'] ?? null;
        if (!$resourcePageBlocks) {
            // Set fallback blocks if the theme has no blocks configuration.
            $resourcePageBlocks = [
                'items' => [
                    'main' => [
                        'mediaEmbeds',
                        'values',
                        'itemSets',
                        'sitePages',
                        'mediaLinks',
                        'linkedResources',
                    ]
                ],
                'item_sets' => [
                    'main' => [
                        'values',
                    ]
                ],
                'media' => [
                    'main' => [
                        'values',
                    ]
                ],
            ];
        }

        // Merge the theme's block config with the block config set in module
        // configuration files.
        return array_merge_recursive(
            $this->standardizeResourcePageBlocks($resourcePageBlocks),
            $this->standardizeResourcePageBlocks($this->resourcePageBlocks)
        );
    }

    /**
     * Standardize resource page blocks into an expected structure.
     *
     * @param mixed $blocksIn
     * @return array
     */
    public function standardizeResourcePageBlocks($blocksIn)
    {
        $blocksIn = is_array($blocksIn) ? $blocksIn : [];
        $blocksOut = [];
        if (isset($blocksIn['items']['main']) && is_array($blocksIn['items']['main'])) {
            $blocksOut['items']['main'] = $blocksIn['items']['main'];
        } else {
            $blocksOut['items']['main'] = [];
        }
        if (isset($blocksIn['item_sets']['main']) && is_array($blocksIn['item_sets']['main'])) {
            $blocksOut['item_sets']['main'] = $blocksIn['item_sets']['main'];
        } else {
            $blocksOut['item_sets']['main'] = [];
        }
        if (isset($blocksIn['media']['main']) && is_array($blocksIn['media']['main'])) {
            $blocksOut['media']['main'] = $blocksIn['media']['main'];
        } else {
            $blocksOut['media']['main'] = [];
        }
        return $blocksOut;
    }
}
