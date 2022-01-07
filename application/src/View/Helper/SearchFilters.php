<?php
namespace Omeka\View\Helper;

use Omeka\Api\Adapter\ResourceAdapter;
use Omeka\Api\Exception\NotFoundException;
use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering search filters.
 */
class SearchFilters extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/search-filters';

    /**
     * @var ResourceAdapter
     */
    protected $resourceAdapter;

    public function __construct(ResourceAdapter $resourceAdapter)
    {
        $this->resourceAdapter = $resourceAdapter;
    }

    /**
     * Render filters from search query.
     *
     * @return array
     */
    public function __invoke($partialName = null, array $query = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $translate = $this->getView()->plugin('translate');

        $filters = [];
        $view = $this->getView();
        $api = $view->api();
        $query = $query ?? $view->params()->fromQuery();
        $queryTypes = [
            'eq' => $translate('is exactly'),
            'neq' => $translate('is not exactly'),
            'in' => $translate('contains'),
            'nin' => $translate('does not contain'),
            'res' => $translate('is resource with ID'),
            'nres' => $translate('is not resource with ID'),
            'ex' => $translate('has any value'),
            'nex' => $translate('has no values'),
            'lex' => $translate('is a linked resource'),
            'nlex' => $translate('is not a linked resource'),
            'lres' => $translate('is linked with resource with ID'),
            'nlres' => $translate('is not linked with resource with ID'),
        ];

        $withoutValueQueryTypes = [
            'ex',
            'nex',
            'lex',
            'nlex',
        ];

        foreach ($query as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            switch ($key) {
                // Fulltext
                case 'fulltext_search':
                    $filterLabel = $translate('Search full-text');
                    $filters[$filterLabel][] = $value;
                    break;

                // Search by class
                case 'resource_class_id':
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    foreach ($value as $subValue) {
                        if (!is_numeric($subValue)) {
                            continue;
                        }
                        $filterLabel = $translate('Class');
                        try {
                            $filterValue = $translate($api->read('resource_classes', $subValue)->getContent()->label());
                        } catch (NotFoundException $e) {
                            $filterValue = $translate('Unknown class');
                        }
                        $filters[$filterLabel][] = $filterValue;
                    }
                    break;

                // Search values (by property or all)
                case 'property':
                    $index = 0;
                    foreach ($value as $queryRow) {
                        if (!(is_array($queryRow)
                            && array_key_exists('type', $queryRow)
                        )) {
                            continue;
                        }
                        $queryType = $queryRow['type'];
                        if (!isset($queryTypes[$queryType])) {
                            continue;
                        }
                        $value = $queryRow['text'] ?? null;
                        // An empty string "" is not a value, but "0" is a value.
                        if (in_array($queryType, $withoutValueQueryTypes, true)) {
                            $value = null;
                        } elseif ((is_array($value) && !count($value)) || !strlen((string) $value)) {
                            continue;
                        }
                        $joiner = $queryRow['joiner'] ?? null;
                        $queriedProperties = $queryRow['property'] ?? null;
                        // Properties may be an array with an empty value
                        // (any property) in advanced form, so remove empty
                        // strings from it, in which case the check should
                        // be skipped.
                        if (is_array($queriedProperties) && in_array('', $queriedProperties, true)) {
                            $queriedProperties = [];
                        }
                        if ($queriedProperties) {
                            $propertyIds = $this->resourceAdapter->getPropertyIds($queriedProperties);
                            $properties = $propertyIds ? $api->search('properties', ['id' => $propertyIds])->getContent() : [];
                            if ($properties) {
                                $propertyLabel = [];
                                foreach ($properties as $property) {
                                    $propertyLabel[] = $translate($property->label());
                                }
                                $propertyLabel = implode(' ' . $translate('OR') . ' ', $propertyLabel);
                            } else {
                                $propertyLabel = $translate('Unknown property');
                            }
                        } else {
                            $propertyLabel = $translate('[Any property]');
                        }
                        $filterLabel = $propertyLabel . ' ' . $queryTypes[$queryType];
                        if ($index > 0) {
                            if ($joiner === 'or') {
                                $filterLabel = $translate('OR') . ' ' . $filterLabel;
                            } else {
                                $filterLabel = $translate('AND') . ' ' . $filterLabel;
                            }
                        }

                        $filters[$filterLabel][] = $value;
                        $index++;
                    }
                    break;

                case 'search':
                    $filterLabel = $translate('Search');
                    $filters[$filterLabel][] = $value;
                    break;

                // Search resource template
                case 'resource_template_id':
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    foreach ($value as $subValue) {
                        if (!is_numeric($subValue)) {
                            continue;
                        }
                        $filterLabel = $translate('Template');
                        try {
                            $filterValue = $api->read('resource_templates', $subValue)->getContent()->label();
                        } catch (NotFoundException $e) {
                            $filterValue = $translate('Unknown template');
                        }
                        $filters[$filterLabel][] = $filterValue;
                    }
                    break;

                // Search item set
                case 'item_set_id':
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    foreach ($value as $subValue) {
                        if (!is_numeric($subValue)) {
                            continue;
                        }
                        $filterLabel = $translate('Item set');
                        try {
                            $filterValue = $api->read('item_sets', $subValue)->getContent()->displayTitle();
                        } catch (NotFoundException $e) {
                            $filterValue = $translate('Unknown item set');
                        }
                        $filters[$filterLabel][] = $filterValue;
                    }
                    break;

                // Search user
                case 'owner_id':
                    $filterLabel = $translate('User');
                    try {
                        $filterValue = $api->read('users', $value)->getContent()->name();
                    } catch (NotFoundException $e) {
                        $filterValue = $translate('Unknown user');
                    }
                    $filters[$filterLabel][] = $filterValue;
                    break;

                case 'site_id':
                    $filterLabel = $translate('Site');
                    try {
                        $filterValue = $api->read('sites', $value)->getContent()->title();
                    } catch (NotFoundException $e) {
                        $filterValue = $translate('Unknown site');
                    }
                    $filters[$filterLabel][] = $filterValue;
                    break;
            }
        }

        $result = $view->trigger(
            'view.search.filters',
            ['filters' => $filters, 'query' => $query],
            true
        );
        $filters = $result['filters'];

        return $view->partial(
            $partialName,
            [
                'filters' => $filters,
            ]
        );
    }
}
