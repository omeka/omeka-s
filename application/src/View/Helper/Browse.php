<?php
namespace Omeka\View\Helper;

use Laminas\Form\Element;
use Laminas\Form\FormElementManager;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Stdlib\Browse as BrowseService;

class Browse extends AbstractHelper
{
    protected BrowseService $browseService;
    protected FormElementManager $formElementManager;

    public function __construct(BrowseService $browseService, FormElementManager $formElementManager)
    {
        $this->browseService = $browseService;
        $this->formElementManager = $formElementManager;
    }

    public function getBrowseService() : BrowseService
    {
        return $this->browseService;
    }
    public function getFormElementManager() : FormElementManager
    {
        return $this->formElementManager;
    }

    /**
     * Get the sort selector.
     *
     * Pass a resource type (string) to use configured/default sorts. Otherwise,
     * pass a sort configuration (array).
     *
     * @param string|array $resourceTypeOrSortConfig
     */
    public function renderSortSelector($resourceTypeOrSortConfig) : string
    {
        $view = $this->getView();
        $context = $view->status()->isAdminRequest() ? 'admin' : 'public';
        if (is_string($resourceTypeOrSortConfig)) {
            $sortConfig = $this->getBrowseService()->getSortConfig($context, $resourceTypeOrSortConfig);
        } elseif (is_array($resourceTypeOrSortConfig)) {
            $sortConfig = $resourceTypeOrSortConfig;
        } else {
            $sortConfig = [];
        }
        if (!$sortConfig) {
            // Do not render the sort selector if there is no configuration.
            return '';
        }
        $query = $view->params()->fromQuery();
        $isFulltextSearch = (isset($query['fulltext_search']) && '' !== trim($query['fulltext_search']));
        if ($isFulltextSearch) {
            // Add "Relevance" to sort_by if this is a fulltext search.
            $sortConfig[''] = 'Relevance'; // @translate
        }
        $args = [
            'sortConfig' => $sortConfig,
            'sortByQuery' => (isset($query['sort_by_default']) && $isFulltextSearch) ? '' : $view->params()->fromQuery('sort_by'),
            'sortOrderQuery' => (isset($query['sort_order_default']) && $isFulltextSearch) ? 'desc' : $view->params()->fromQuery('sort_order'),
        ];
        $args = $view->trigger('view.sort-selector', $args, true);
        return $view->partial('common/sort-selector', (array) $args);
    }

    /**
     * Get the header row for a resource type.
     */
    public function renderHeaderRow(string $resourceType) : string
    {
        $view = $this->getView();
        $escapeHelper = $view->plugin('escapeHtml');
        $context = $view->status()->isAdminRequest() ? 'admin' : 'public';
        $headerRow = [];
        foreach ($this->getBrowseService()->getColumnsData($context, $resourceType) as $columnData) {
            if (!$this->getBrowseService()->columnTypeIsKnown($columnData['type'])) {
                continue; // Skip unknown column types.
            }
            $headerRow[] = sprintf(
                '<th class="column-%s">%s</th>',
                $escapeHelper($columnData['type']),
                $escapeHelper($this->getHeader($columnData))
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
        $columnType = $this->getBrowseService()->getColumnType($columnData['type']);
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
        foreach ($this->getBrowseService()->getColumnsData($context, $resourceType) as $columnData) {
            if (!$this->getBrowseService()->columnTypeIsKnown($columnData['type'])) {
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
        $columnType = $this->getBrowseService()->getColumnType($columnData['type']);
        return $columnType->renderContent($view, $resource, $columnData) ?? $view->escapeHtml($columnData['default']);
    }

    /**
     * Get the markup for the column type select element.
     */
    public function getColumnTypeSelect(string $resourceType) : string
    {
        $formElements = $this->getFormElementManager();
        $columnTypes = $this->getBrowseService()->getColumnTypeManager();
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
        usort($valueOptions, fn ($a, $b) => strcmp($a['label'], $b['label']));
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
        $formElements = $this->getFormElementManager();
        $columnTypes = $this->getBrowseService()->getColumnTypeManager();
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
        if ($this->getBrowseService()->columnTypeIsKnown($columnData['type'])) {
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
}
