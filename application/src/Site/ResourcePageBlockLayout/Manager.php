<?php
namespace Omeka\Site\ResourcePageBlockLayout;

use Omeka\ServiceManager\AbstractPluginManager;
use Omeka\Site\Theme\Theme;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ResourcePageBlockLayoutInterface::class;

    protected $resourcePageBlocksDefault;

    protected $siteSettings;

    const RESOURCE_PAGE_BLOCKS_DEFAULT = [
        'items' => [
            'main' => [
                'mediaEmbeds',
                'values',
                'itemSets',
                'sitePages',
                'mediaLinks',
                'linkedResources',
            ],
        ],
        'item_sets' => [
            'main' => [
                'values',
            ],
        ],
        'media' => [
            'main' => [
                'mediaRender',
                'values',
            ],
        ],
    ];

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Fallback($name);
        }
        return $instance;
    }

    public function setResourcePageBlocksDefault(array $resourcePageBlocksDefault)
    {
        $this->resourcePageBlocksDefault = $resourcePageBlocksDefault;
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
            $resourcePageBlocks = self::RESOURCE_PAGE_BLOCKS_DEFAULT;
        }

        // Merge the theme's block config with the block config set in module
        // configuration files.
        return array_merge_recursive(
            $this->standardizeResourcePageBlocks($resourcePageBlocks),
            $this->standardizeResourcePageBlocks($this->resourcePageBlocksDefault)
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
        $resourcePageRegions = $themeConfig['resource_page_regions'] ?? [];
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
        foreach (['items', 'item_sets', 'media'] as $resourceName) {
            if (isset($blocksIn[$resourceName]) && is_array($blocksIn[$resourceName])) {
                foreach ($blocksIn[$resourceName] as $regionName => $blockLayouts) {
                    if (!is_array($blockLayouts)) {
                        $blockLayouts = [];
                    }
                    $blocksOut[$resourceName][$regionName] = array_filter(array_map('strval', $blockLayouts));
                }
            } else {
                $blocksOut[$resourceName] = ['main' => []];
            }
        }
        return $blocksOut;
    }

    /**
     * Standardize resource page regions into an expected structure.
     *
     * Use to prevent data corruption when processing user data. Note that the
     * "main" region is required and will be included if not present.
     *
     * @param mixed $regionsIn
     * @return array
     */
    public function standardizeResourcePageRegions($regionsIn)
    {
        $regionsIn = is_array($regionsIn) ? $regionsIn : [];
        $regionsOut = [];
        foreach (['items', 'item_sets', 'media'] as $resourceName) {
            if (isset($regionsIn[$resourceName]) && is_array($regionsIn[$resourceName])) {
                foreach ($regionsIn[$resourceName] as $regionName => $regionLabel) {
                    $regionsOut[$resourceName][$regionName] = strval($regionLabel);
                }
            } else {
                $regionsOut[$resourceName] = [];
            }
            if (!isset($regionsOut[$resourceName]['main'])) {
                $regionsOut[$resourceName]['main'] = 'Main'; // @translate
            }
        }
        return $regionsOut;
    }
}
