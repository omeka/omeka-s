<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\ColumnType\ColumnTypeInterface;
use Omeka\ColumnType\Unknown as UnknownColumnType;
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

    public function getHeaders(string $resourceType) : array
    {
        $headers = [];
        foreach ($this->getColumnsDataValid($resourceType) as $columnData) {
            $headers[] = $this->getHeader($columnData);
        }
        return $headers;
    }

    public function getHeader(array $columnData) : string
    {
        $view = $this->getView();
        $columnType = $this->getColumnType($columnData['type']);
        // If the user does not defaine a header, get the one defined by the
        // column service. Note that we don't translate user-defined headers.
        if (isset($columnData['header']) && '' !== trim($columnData['header'])) {
            return $columnData['header'];
        }
        return $view->translate($columnType->renderHeader($view, $columnData));
    }

    public function getContents(string $resourceType, AbstractResourceEntityRepresentation $resource) : array
    {
        $contents = [];
        foreach ($this->getColumnsDataValid($resourceType) as $columnData) {
            $contents[] = $this->getContent($resource, $columnData);
        }
        return $contents;
    }

    public function getContent(AbstractResourceEntityRepresentation $resource, array $columnData) : ?string
    {
        $view = $this->getView();
        $columnType = $this->getColumnType($columnData['type']);
        // If the column service returns null, use the user-defined default,
        // if any. Note that we don't translate user-defined defaults.
        return  $columnType->renderContent($view, $resource, $columnData) ?? $columnData['default'];
    }

    public function getColumnsData(string $resourceType, ?int $userId = null) : array
    {
        $view = $this->getView();
        $userSettings = $this->services->get('Omeka\Settings\User');
        $columnsData = $userSettings->get(sprintf('columns_%s', $resourceType), null, $userId);
        if (!is_array($columnsData) || !$columnsData) {
            // Columns data not configured or malformed. Set the default.
            $columnsData = self::DEFAULT_COLUMNS_DATA;
        }
        foreach ($columnsData as $index => &$columnData) {
            if (!is_array($columnData)) {
                // Skip columns that are not an array.
                continue;
            }
            if (!isset($columnData['type'])) {
                // Skip columns without a type.
                continue;
            }
            // Add required keys if not present.
            $columnData['default'] ??= null;
            $columnData['header'] ??= null;
        }
        return $columnsData;
    }

    public function getColumnsDataValid(string $resourceType, ?int $userId = null) : array
    {
        $columnsData = [];
        foreach ($this->getColumnsData($resourceType, $userId) as $columnData) {
            $columnType = $this->getColumnType($columnData['type']);
            if (!($this->columnTypeIsKnown($columnType))) {
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

    public function getColumnTypeSelect(string $resourceType)
    {
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
        $select = new Element\Select('column_type_select');
        $select->setValueOptions($valueOptions)
            ->setEmptyOption('Add a column…') // @translate
            ->setAttribute('class', 'columns-column-type-select');
        return $this->getView()->formElement($select);
    }

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

        if ($this->columnTypeIsKnown($columnType)) {
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
