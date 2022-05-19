<?php
namespace Omeka\Stdlib;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\ColumnType\ColumnTypeInterface;

class Browse
{
    protected ServiceLocatorInterface $services;

    protected array $columnDefaults;
    protected array $browseDefaults;
    protected array $sortDefaults;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        $config = $services->get('Config');
        $this->columnDefaults = $config['column_defaults'];
        $this->browseDefaults = $config['browse_defaults'];
        $this->sortDefaults = $config['sort_defaults'];
    }

    /**
     * Get a column type by name.
     */
    public function getColumnType(string $columnType) : ColumnTypeInterface
    {
        return $this->services->get('Omeka\ColumnTypeManager')->get($columnType);
    }

    /**
     * Is this column type known?
     */
    public function columnTypeIsKnown(string $columnType) : bool
    {
        $columnTypes = $this->services->get('Omeka\ColumnTypeManager');
        return $columnTypes->has($columnType);
    }

    /**
     * Get the sort configuration.
     *
     * The sort configuration is an array:
     *
     * [
     *   'sort_by_query_param' => 'Sort by label',
     *   'another_sort_by_query_param' => 'Another sort by label',
     * ]
     */
    public function getSortConfig(string $context, string $resourceType) : array
    {
        $sortConfig = [];
        $browseHelper = $this->services->get('ViewHelperManager')->get('browse');
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
                $sortConfig[$sortBy] = $label;
            }
        }
        // Include any other sorts added by the sort-config event.
        $eventManager = $this->services->get('EventManager');
        $args = $eventManager->prepareArgs([
            'context' => $context,
            'resourceType' => $resourceType,
            'sortConfig' => $sortConfig,
        ]);
        $eventManager->trigger('sort-config', null, $args);
        $sortConfig = $args['sortConfig'];
        natsort($sortConfig);
        return $sortConfig;
    }

    /**
     * Get the browse configuration.
     *
     * The browse configuration is an array with three elements:
     *   1. The default sort_by value
     *   2. The default sort_order value
     *   3. The default page value
     */
    public function getBrowseConfig(string $context, string $resourceType, ?int $userId = null) : array
    {
        $userSettings = $this->services->get('Omeka\Settings\User');
        // First, get the user-configured browse defaults, if any. Set the
        // defaults from the config file if they're not configured or malformed.
        $browseDefaultsSetting = sprintf('browse_defaults_%s_%s', $context, $resourceType);
        $browseConfig = $userSettings->get($browseDefaultsSetting, null, $userId);
        if (!is_array($browseConfig) || !isset($browseConfig[0]) || !is_string($browseConfig[0])) {
            $browseConfig = $this->browseDefaults[$context][$resourceType] ?? [null, 'desc', 1];
        }
        // Standardize the defaults before returning.
        $browseConfig = [
            $browseConfig[0] ?? null,
            $browseConfig[1] ?? 'desc',
            $browseConfig[2] ?? 1,
        ];
        return $browseConfig;
    }

    /**
     * Get data for all columns.
     */
    public function getColumnsData(string $context, string $resourceType, ?int $userId = null) : array
    {
        $userSettings = $this->services->get('Omeka\Settings\User');
        // First, get the user-configured columns data, if any. Set the default
        // if data is not configured or malformed.
        $userColumnsSetting = sprintf('columns_%s_%s', $context, $resourceType);
        $userColumnsData = $userSettings->get($userColumnsSetting, null, $userId);
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
