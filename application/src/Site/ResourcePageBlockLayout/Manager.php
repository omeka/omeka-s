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

    const DEFAULT_RESOURCE_PAGE_BLOCKS = [
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

    const DEFAULT_RESOURCE_PAGE_REGIONS = [
        'items' => [
            'main' => 'Main', // @translate
        ],
        'item_sets' => [
            'main' => 'Main', // @translate
        ],
        'media' => [
            'main' => 'Main', // @translate
        ],
    ];

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
     * Themes may register default blocks by using the following template in
     * their theme.ini file:
     *
     * resource_page_blocks.<resource_name>.<region_name>[] = "<block_layout_name>"
     *
     * - resource_name: The name of the resource page's resource: items, item_sets, or media.
     * - region_name: The name of the region within the resource page.
     * - block_layout_name: The name of the block layout.
     *
     * Note the [] to create an array of block layouts that will be rendered in
     * the given order.
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
            // Set deafult blocks if the theme has no blocks configuration.
            $resourcePageBlocks = self::DEFAULT_RESOURCE_PAGE_BLOCKS;
        }

        // Merge the theme's block config with the block config set in module
        // configuration files.
        return array_merge_recursive(
            $this->standardizeResourcePageBlocks($resourcePageBlocks),
            $this->standardizeResourcePageBlocks($this->resourcePageBlocks)
        );
    }

    /**
     * Get the current resource page regions configuration for a theme.
     *
     * Themes may register regions by using the following template in their
     * theme.ini file:
     *
     * resource_page_regions.<resource_name>.<region_name> = "<region_label>"
     *
     * - resource_name: The name of the resource page's resource: items, item_sets, or media.
     * - region_name: The name of the region within the resource page.
     * - region_label: The human-readable label of the region.
     *
     * @param Theme $theme
     * @return array
     */
    public function getResourcePageRegions(Theme $theme)
    {
        $themeConfig = $theme->getConfigSpec();
        $resourcePageRegions = $themeConfig['resource_page_regions'] ?? null;
        if (!$resourcePageRegions) {
            // Set default regions if the theme has no regions configuration.
            $resourcePageRegions = self::DEFAULT_RESOURCE_PAGE_REGIONS;
        }
        return $this->standardizeResourcePageRegions($resourcePageRegions);
    }

    /**
     * Standardize resource page blocks into an expected structure.
     *
     * Use to prevent data corruption when processing user data.
     *
     * @param mixed $blocksIn
     * @return array
     */
    public function standardizeResourcePageBlocks($blocksIn)
    {
        $blocksIn = is_array($blocksIn) ? $blocksIn : [];
        $blocksOut = [];
        if (isset($blocksIn['items']) && is_array($blocksIn['items'])) {
            foreach ($blocksIn['items'] as $regionName => $blockLayouts) {
                $blocksOut['items'][$regionName] = array_filter(array_map('strval', $blockLayouts));
            }
        } else {
            $blocksOut['items'] = [];
        }
        if (isset($blocksIn['item_sets']) && is_array($blocksIn['item_sets'])) {
            foreach ($blocksIn['item_sets'] as $regionName => $blockLayouts) {
                $blocksOut['item_sets'][$regionName] = array_filter(array_map('strval', $blockLayouts));
            }
        } else {
            $blocksOut['item_sets'] = [];
        }
        if (isset($blocksIn['media']) && is_array($blocksIn['media'])) {
            foreach ($blocksIn['media'] as $regionName => $blockLayouts) {
                $blocksOut['media'][$regionName] = array_filter(array_map('strval', $blockLayouts));
            }
        } else {
            $blocksOut['media'] = [];
        }
        return $blocksOut;
    }

    /**
     * Standardize resource page regions into an expected structure.
     *
     * Use to prevent data corruption when processing user data.
     *
     * @param mixed $regionsIn
     * @return array
     */
    public function standardizeResourcePageRegions($regionsIn)
    {
        $regionsIn = is_array($regionsIn) ? $regionsIn : [];
        $regionsOut = [];
        if (isset($regionsIn['items']) && is_array($regionsIn['items'])) {
            foreach ($regionsIn['items'] as $regionName => $regionLabel) {
                $regionsOut['items'][$regionName] = strval($regionLabel);
            }
        } else {
            $regionsOut['items'] = [];
        }
        if (isset($regionsIn['item_sets']) && is_array($regionsIn['item_sets'])) {
            foreach ($regionsIn['item_sets'] as $regionName => $regionLabel) {
                $regionsOut['item_sets'][$regionName] = strval($regionLabel);
            }
        } else {
            $regionsOut['item_sets'] = [];
        }
        if (isset($regionsIn['media']) && is_array($regionsIn['media'])) {
            foreach ($regionsIn['media'] as $regionName => $regionLabel) {
                $regionsOut['media'][$regionName] = strval($regionLabel);
            }
        } else {
            $regionsOut['media'] = [];
        }
        return $regionsOut;
    }
}
