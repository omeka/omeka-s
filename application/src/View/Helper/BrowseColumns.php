<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\ColumnType\ColumnTypeInterface;
use Omeka\ColumnType\Unknown as UnknownColumnType;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

class BrowseColumns extends AbstractHelper
{
    const DEFAULT_COLUMNS_DATA = [
        [
            'type' => 'id',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'is_public',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'resource_class',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'resource_template',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'owner',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'created',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'modified',
            'header' => null,
            'default' => null,
        ],
        [
            'type' => 'value',
            'header' => null,
            'default' => null,
            'property_term' => 'dcterms:subject',
        ],
    ];

    protected ServiceLocatorInterface $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    public function getHeaders(string $resourceType) : array
    {
        $view = $this->getView();
        $headers = [];
        foreach ($this->getValidColumnsData($resourceType) as $columnData) {
            // If the user does not defaine a header, get the one defined by the
            // column service. Note that we don't translate user-defined headers.
            $columnType = $this->getColumnType($columnData['type']);
            $headers[] = $columnData['header'] ?? $view->translate($columnType->renderHeader($view, $columnData));
        }
        return $headers;
    }

    public function getContents(string $resourceType, AbstractResourceEntityRepresentation $resource) : array
    {
        $view = $this->getView();
        $contents = [];
        foreach ($this->getValidColumnsData($resourceType) as $columnData) {
            // If the column service returns null, use the user-defined default,
            // if any. Note that we don't translate user-defined defaults.
            $columnType = $this->getColumnType($columnData['type']);
            $contents[] = $columnType->renderContent($view, $resource, $columnData) ?? $columnData['default'];
        }
        return $contents;
    }

    public function getColumnsData(string $resourceType) : array
    {
        $userSettings = $this->services->get('Omeka\Settings\User');
        $columnsData = $userSettings->get(sprintf('browse_columns_%s', $resourceType));
        if (!is_array($columnsData) || !$columnsData) {
            // Columns data not configured or invalid. Set the default.
            $columnsData = self::DEFAULT_COLUMNS_DATA;
        }
        // Standardize column data.
        foreach ($columnsData as &$columnData) {
            if (!is_array($columnData)) {
                // Column data must be an array.
                $columnData = [];
            }
            // Add required keys if not present.
            $columnData['type'] ??= '';
            $columnData['header'] ??= null;
            $columnData['default'] ??= null;
        }
        return $columnsData;
    }

    public function getValidColumnsData(string $resourceType) : array
    {
        $columnsData = [];
        foreach ($this->getColumnsData($resourceType) as $columnData) {
            $columnType = $this->getColumnType($columnData['type']);
            if (!$this->columnTypeIsKnown($columnType)) {
                // Skip unknown column types.
                continue;
            }
            if (!$columnType->dataIsValid($columnData)) {
                // Skip columns with invalid data.
                continue;
            }
            $columnsData[] = $columnData;
        }
        return $columnsData;
    }

    /**
     * Get a column type by name.
     */
    public function getColumnType(string $columnType) : ColumnTypeInterface
    {
        return $this->services->get('Omeka\ColumnTypeManager')->get($columnType);
    }

    /**
     * Prepare the data forms for all column types.
     */
    public function prepareDataForms() : void
    {
        $columnTypes = $this->services->get('Omeka\ColumnTypeManager');
        foreach ($columnTypes->getRegisteredNames() as $columnTypeName) {
            $this->getColumnType($columnTypeName)->prepareDataForm($this->getView());
        }
    }

    /**
     * Is this column type known?
     */
    public function columnTypeIsKnown(ColumnTypeInterface $columnType) : bool
    {
        return !($columnType instanceof UnknownColumnType);
    }
}
