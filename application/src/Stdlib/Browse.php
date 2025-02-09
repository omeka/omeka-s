<?php
namespace Omeka\Stdlib;

use Laminas\EventManager\EventManager;
use Laminas\View\HelperPluginManager;
use Omeka\ColumnType\ColumnTypeInterface;
use Omeka\ColumnType\Manager as ColumnTypeManager;
use Omeka\Settings\SiteSettings;
use Omeka\Settings\UserSettings;

class Browse
{
    protected array $columnDefaults;
    protected array $browseDefaults;
    protected array $sortDefaults;
    protected ColumnTypeManager $columnTypeManager;
    protected HelperPluginManager $viewHelperManager;
    protected EventManager $eventManager;
    protected UserSettings $userSettings;
    protected SiteSettings $siteSettings;

    public function __construct(
        array $columnDefaults,
        array $browseDefaults,
        array $sortDefaults,
        ColumnTypeManager $columnTypeManager,
        HelperPluginManager $viewHelperManager,
        EventManager $eventManager,
        UserSettings $userSettings,
        SiteSettings $siteSettings
    ) {
        $this->columnDefaults = $columnDefaults;
        $this->browseDefaults = $browseDefaults;
        $this->sortDefaults = $sortDefaults;
        $this->columnTypeManager = $columnTypeManager;
        $this->viewHelperManager = $viewHelperManager;
        $this->eventManager = $eventManager;
        $this->userSettings = $userSettings;
        $this->siteSettings = $siteSettings;
    }

    public function getColumnTypeManager() : ColumnTypeManager
    {
        return $this->columnTypeManager;
    }
    public function getViewHelperManager() : HelperPluginManager
    {
        return $this->viewHelperManager;
    }
    public function getEventManager() : EventManager
    {
        return $this->eventManager;
    }
    public function getUserSettings() : UserSettings
    {
        return $this->userSettings;
    }
    public function getSiteSettings() : SiteSettings
    {
        return $this->siteSettings;
    }

    /**
     * Get a column type by name.
     */
    public function getColumnType(string $columnType) : ColumnTypeInterface
    {
        return $this->getColumnTypeManager()->get($columnType);
    }

    /**
     * Is this column type known?
     */
    public function columnTypeIsKnown(string $columnType) : bool
    {
        return $this->getColumnTypeManager()->has($columnType);
    }

    /**
     * Get the sort configuration.
     *
     * The sort configuration is an array:
     * [
     *   '<sort_by_param_1>' => '<Sort by label 1>',
     *   '<sort_by_param_2' => '<Sort by label 2>',
     * ]
     */
    public function getSortConfig(string $context, string $resourceType) : array
    {
        $browseHelper = $this->getViewHelperManager()->get('browse');
        $translateHelper = $this->getViewHelperManager()->get('translate');
        $sortConfig = [];
        // Include sorts from user-configured columns.
        foreach ($this->getColumnsData($context, $resourceType) as $columnData) {
            if (!$this->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $columnType = $this->getColumnType($columnData['type']);
            $sortBy = $columnType->getSortBy($columnData);
            if (!$sortBy) {
                continue; // This column cannot be sorted.
            }
            $sortConfig[$sortBy] = $browseHelper->getHeader($columnData);
        }
        // Include default sorts that are not configured.
        $sortDefaults = $this->sortDefaults[$context][$resourceType] ?? [];
        foreach ($sortDefaults as $sortBy => $label) {
            if (!isset($sortConfig[$sortBy])) {
                $sortConfig[$sortBy] = $translateHelper($label);
            }
        }
        // Include any other sorts added by the sort-config event.
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs([
            'context' => $context,
            'resourceType' => $resourceType,
            'sortConfig' => $sortConfig,
        ]);
        $eventManager->trigger('sort-config', null, $args);
        $sortConfig = $args['sortConfig'];
        // Include the custom sort by, if any.
        $browseConfig = $this->getBrowseConfig($context, $resourceType);
        if (!isset($sortConfig[$browseConfig['sort_by']])) {
            $customLabel = 'Custom (%s)'; // @translate
            $sortConfig[$browseConfig['sort_by']] = sprintf($translateHelper($customLabel), $browseConfig['sort_by']);
        }
        natsort($sortConfig);
        return $sortConfig;
    }

    /**
     * Get the browse configuration.
     *
     * The browse configuration is an array containing two keys:
     * [
     *   'sort_by' => '<sort_by_param>',
     *   'sort_order' => '<sort_order_param>',
     * ]
     *
     * Note that context determines the origin of user-configured data: "public"
     * context derives from site settings; "admin" context derives from user
     * settings.
     */
    public function getBrowseConfig(string $context, string $resourceType, ?int $userId = null) : array
    {
        // First, get the user-configured browse defaults, if any. Set the
        // defaults from the config file if they're not configured or malformed.
        $browseDefaultsSetting = sprintf('browse_defaults_%s_%s', $context, $resourceType);
        $browseConfig = ('public' === $context)
            ? $this->getSiteSettings()->get($browseDefaultsSetting, null)
            : $this->getUserSettings()->get($browseDefaultsSetting, null, $userId);

        if (!is_array($browseConfig)
            || !isset($browseConfig['sort_by'])
            || !is_string($browseConfig['sort_by'])
            || '' === trim($browseConfig['sort_by'])
            || !isset($browseConfig['sort_order'])
            || !is_string($browseConfig['sort_order'])
            || !in_array($browseConfig['sort_order'], ['desc', 'asc'])
        ) {
            $browseConfig = $this->browseDefaults[$context][$resourceType] ?? [];
        }
        // Standardize the defaults before returning.
        $browseConfig = [
            'sort_by' => $browseConfig['sort_by'] ?? 'id',
            'sort_order' => $browseConfig['sort_order'] ?? 'desc',
        ];
        return $browseConfig;
    }

    /**
     * Get data for all columns.
     *
     * Note that context determines the origin of user-configured data: "public"
     * context derives from site settings; "admin" context derives from user
     * settings.
     */
    public function getColumnsData(string $context, string $resourceType, $userId = null) : array
    {
        // First, get the user-configured columns data, if any. Set the default
        // if data is not configured or malformed.
        $userColumnsSetting = sprintf('columns_%s_%s', $context, $resourceType);
        $userColumnsData = ('public' === $context)
            ? $this->getSiteSettings()->get($userColumnsSetting, null)
            : $this->getUserSettings()->get($userColumnsSetting, null, $userId);

        if (!is_array($userColumnsData) || !$userColumnsData) {
            $userColumnsData = $this->columnDefaults[$context][$resourceType] ?? [];
        }
        // Standardize the data before returning.
        $columnsData = [];
        foreach ($userColumnsData as $index => $userColumnData) {
            if (!is_array($userColumnData)) {
                // Skip columns that are not an array.
                continue;
            }
            if (!isset($userColumnData['type'])) {
                // Skip columns without a type.
                continue;
            }
            // Add required keys if not present.
            $userColumnData['default'] ??= null;
            $userColumnData['header'] ??= null;
            $columnsData[] = $userColumnData;
        }
        return $columnsData;
    }
}
