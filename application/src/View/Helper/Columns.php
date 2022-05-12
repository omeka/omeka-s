<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\ColumnType\ColumnTypeInterface;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\AbstractHelper;

class Columns extends AbstractHelper
{
    protected ServiceLocatorInterface $services;

    protected $columnDefaults;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        $config = $services->get('Config');
        $this->columnDefaults = $config['column_defaults'];
    }

    /**
     * Get the sort configuration for use by the sortSelector view helper.
     */
    public function getSortConfig(string $resourceType, array $alwaysInclude = []) : array
    {
        $view = $this->getView();
        $context = $view->status()->isAdminRequest() ? 'admin' : 'public';
        $sortConfig = [];
        foreach ($this->getColumnsData($context, $resourceType) as $columnData) {
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
        // Include any passed sort bys.
        foreach ($alwaysInclude as $sortBy) {
            if (!array_search($sortBy['value'], array_column($sortConfig, 'value'))) {
                array_unshift($sortConfig, $sortBy);
            }
        }
        return $sortConfig;
    }

    /**
     * Get the header row for a resource type.
     */
    public function renderHeaderRow(string $resourceType) : string
    {
        $view = $this->getView();
        $context = $view->status()->isAdminRequest() ? 'admin' : 'public';
        $headerRow = [];
        foreach ($this->getColumnsData($context, $resourceType) as $columnData) {
            if (!$this->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $headerRow[] = sprintf(
                '<th class="column-%s">%s</th>',
                $columnData['type'],
                $this->getHeader($columnData)
            );
        }
        return implode("\n", $headerRow);
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
     * Get the content row for a resource.
     */
    public function renderContentRow(string $resourceType, AbstractEntityRepresentation $resource) : string
    {
        $view = $this->getView();
        $context = $view->status()->isAdminRequest() ? 'admin' : 'public';
        $contentRow = [];
        foreach ($this->getColumnsData($context, $resourceType) as $columnData) {
            if (!$this->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $contentRow[] = sprintf(
                '<td class="column-%s">%s</td>',
                $columnData['type'],
                $this->getContent($resource, $columnData)
            );
        }
        return implode("\n", $contentRow);
    }

    /**
     * Get a column content.
     *
     * If the column service returns null, use the user-defined default, if any.
     * Note that we don't translate user-defined defaults.
     */
    public function getContent(AbstractEntityRepresentation $resource, array $columnData) : ?string
    {
        $view = $this->getView();
        $columnType = $this->getColumnType($columnData['type']);
        return  $columnType->renderContent($view, $resource, $columnData) ?? $columnData['default'];
    }

    /**
     * Get data for all columns.
     */
    public function getColumnsData(string $context, string $resourceType, ?int $userId = null) : array
    {
        $view = $this->getView();
        $userSettings = $this->services->get('Omeka\Settings\User');
        // First, get the user-configured columns data, if any. Set the default
        // if data is not configured or malformed. If there is no default, just
        // include an ID column, which is common to all resource types.
        $userColumnsData = $userSettings->get(sprintf('admin_columns_%s', $resourceType), null, $userId);
        if (!is_array($userColumnsData) || !$userColumnsData) {
            $userColumnsData = $this->columnDefaults[$context][$resourceType] ?? [['type' => 'id']];
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
                'data-column-data-key' => 'header',
            ]);
            $columnForm[] = $view->formRow($headerInput);

            $defaultInput = $formElements->get(Element\Text::class);
            $defaultInput->setName('column_default');
            $defaultInput->setOptions([
                'label' => 'Default', // @translate
            ]);
            $defaultInput->setAttributes([
                'value' => $columnData['default'] ?? '',
                'data-column-data-key' => 'default',
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
