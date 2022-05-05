<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\ColumnType\ColumnTypeInterface;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

class Columns extends AbstractHelper
{
    const DEFAULT_COLUMNS_DATA = [
        [
            'type' => 'resource_class',
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
    ];

    protected ServiceLocatorInterface $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    /**
     * Get the sort configuration for use by the sortSelector view helper.
     */
    public function getSortConfig(string $resourceType) : array
    {
        $view = $this->getView();
        // Always include sort by Title.
        $sortConfig = [
            [
                'value' => 'title',
                'label' => $view->translate('Title'),
            ]
        ];
        foreach ($this->getColumnsData($resourceType) as $columnData) {
            if (!$this->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $columnType = $this->getColumnType($columnData['type']);
            $sortBy = $columnType->getSortBy($columnData);
            if (!$sortBy) {
                continue; // This column cannot be sorted.
            }
            $sortConfig[] = [
                'value' => $sortBy,
                'label' => $this->getHeader($columnData),
            ];
        }
        // Always include sort by ID.
        if (!array_search('id', array_column($sortConfig, 'value'))) {
            $sortConfig[] = [
                'value' => 'id',
                'label' => $view->translate('ID'),
            ];
        }
        // Always include sort by Created.
        if (!array_search('created', array_column($sortConfig, 'value'))) {
            $sortConfig[] = [
                'value' => 'created',
                'label' => $view->translate('Created'),
            ];
        }
        return $sortConfig;
    }

    /**
     * Get all column headers for a resource type.
     */
    public function getHeaders(string $resourceType) : array
    {
        $headers = [];
        foreach ($this->getColumnsData($resourceType) as $columnData) {
            if (!$this->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $headers[] = $this->getHeader($columnData);
        }
        return $headers;
    }

    /**
     * Get a column header.
     *
     * If the user does not define a header, get the one defined by the column
     * service. Note that we don't translate user-defined headers.
     */
    public function getHeader(array $columnData) : string
    {
        $view = $this->getView();
        $columnType = $this->getColumnType($columnData['type']);
        if (isset($columnData['header']) && '' !== trim($columnData['header'])) {
            return $columnData['header'];
        }
        return $view->translate($columnType->renderHeader($view, $columnData));
    }

    /**
     * Get all column contents for a resource type.
     */
    public function getContents(string $resourceType, AbstractResourceEntityRepresentation $resource) : array
    {
        $contents = [];
        foreach ($this->getColumnsData($resourceType) as $columnData) {
            if (!$this->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $contents[] = $this->getContent($resource, $columnData);
        }
        return $contents;
    }

    /**
     * Get a column content.
     *
     * If the column service returns null, use the user-defined default, if any.
     * Note that we don't translate user-defined defaults.
     */
    public function getContent(AbstractResourceEntityRepresentation $resource, array $columnData) : ?string
    {
        $view = $this->getView();
        $columnType = $this->getColumnType($columnData['type']);
        return  $columnType->renderContent($view, $resource, $columnData) ?? $columnData['default'];
    }

    /**
     * Get data for all columns.
     */
    public function getColumnsData(string $resourceType, ?int $userId = null) : array
    {
        $view = $this->getView();
        $userSettings = $this->services->get('Omeka\Settings\User');

        // First, get the user-configured columns data, if any. Set the default
        // if data is not configured or malformed
        $userColumnsData = $userSettings->get(sprintf('columns_%s', $resourceType), null, $userId);
        if (!is_array($userColumnsData) || !$userColumnsData) {
            $userColumnsData = self::DEFAULT_COLUMNS_DATA;
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

    /**
     * Get the markup for the column type select element.
     */
    public function getColumnTypeSelect(string $resourceType) : string
    {
        $formElements = $this->services->get('FormElementManager');
        $columnTypes = $this->services->get('Omeka\ColumnTypeManager');
        $valueOptions = [];
        foreach ($columnTypes->getRegisteredNames() as $columnTypeName) {
            $columnType = $columnTypes->get($columnTypeName);
            if (in_array($resourceType, $columnType->getResourceTypes())) {
                $valueOptions[] = [
                    'value' => $columnTypeName,
                    'label' => $columnType->getLabel(),
                    'attributes' => [
                        'data-max-columns' => $columnType->getMaxColumns(),
                    ],
                ];
            }
        }
        usort($valueOptions, fn($a, $b) => strcmp($a['label'], $b['label']));
        $select = $formElements->get(Element\Select::class);
        $select->setName('column_type_select')
            ->setValueOptions($valueOptions)
            ->setEmptyOption('Add a column…') // @translate
            ->setAttribute('class', 'columns-column-type-select');
        return $this->getView()->formElement($select);
    }

    /**
     * Get the markup for the column edit form.
     */
    public function getColumnForm(array $columnData) : string
    {
        $view = $this->getView();
        $formElements = $this->services->get('FormElementManager');
        $columnTypes = $this->services->get('Omeka\ColumnTypeManager');
        $columnType = $columnTypes->get($columnData['type']);

        $columnForm = [];
        $columnTypeInput = $formElements->get(Element\Text::class);
        $columnTypeInput->setName('column_type');
        $columnTypeInput->setOptions([
            'label' => 'Column type', // @translate
        ]);
        $columnTypeInput->setAttributes([
            'disabled' => true,
            'value' => $columnType->getLabel(),
        ]);
        $columnForm[] = $view->formRow($columnTypeInput);
        if ($this->columnTypeIsKnown($columnData['type'])) {
            $headerInput = $formElements->get(Element\Text::class);
            $headerInput->setName('column_header');
            $headerInput->setOptions([
                'label' => 'Header', // @translate
            ]);
            $headerInput->setAttributes([
                'value' => $columnData['header'] ?? '',
                'data-column-key' => 'header',
            ]);
            $columnForm[] = $view->formRow($headerInput);

            $defaultInput = $formElements->get(Element\Text::class);
            $defaultInput->setName('column_default');
            $defaultInput->setOptions([
                'label' => 'Default', // @translate
            ]);
            $defaultInput->setAttributes([
                'value' => $columnData['default'] ?? '',
                'data-column-key' => 'default',
            ]);
            $columnForm[] = $view->formRow($defaultInput);
        }
        $columnForm[] = $columnType->renderDataForm($view, $columnData);
        return implode($columnForm);
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
}
