<?php
namespace Omeka\View\Helper;

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
     * Render filters from search query.
     *
     * @return array
     */
    public function __invoke($partialName = null, array $query = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $translate = $this->getView()->plugin('translate');

        $filters = [];
        $api = $this->getView()->api();
        $query ??= $this->getView()->params()->fromQuery();
        $queryTypes = [
            'eq' => $translate('is exactly'),
            'neq' => $translate('is not exactly'),
            'in' => $translate('contains'),
            'nin' => $translate('does not contain'),
            'sw' => $translate('starts with'),
            'nsw' => $translate('does not start with'),
            'ew' => $translate('ends with'),
            'new' => $translate('does not end with'),
            'res' => $translate('is resource with ID'),
            'nres' => $translate('is not resource with ID'),
            'ex' => $translate('has any value'),
            'nex' => $translate('has no values'),
        ];

        foreach ($query as $key => $value) {
            if ($value != null) {
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
                                && array_key_exists('property', $queryRow)
                                && array_key_exists('type', $queryRow)
                            )) {
                                continue;
                            }
                            $propertyId = $queryRow['property'];
                            $queryType = $queryRow['type'];
                            $joiner = $queryRow['joiner'] ?? null;
                            $value = $queryRow['text'] ?? null;

                            if (!$value && $queryType !== 'nex' && $queryType !== 'ex') {
                                continue;
                            }
                            if ($propertyId) {
                                if (is_numeric($propertyId)) {
                                    try {
                                        $property = $api->read('properties', $propertyId)->getContent();
                                    } catch (NotFoundException $e) {
                                        $property = null;
                                    }
                                } else {
                                    $property = $api->searchOne('properties', ['term' => $propertyId])->getContent();
                                }

                                if ($property) {
                                    $propertyLabel = $translate($property->label());
                                } else {
                                    $propertyLabel = $translate('Unknown property');
                                }
                            } else {
                                $propertyLabel = $translate('[Any property]');
                            }
                            if (!isset($queryTypes[$queryType])) {
                                continue;
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
                            $filterLabel = $translate('In item set');
                            try {
                                $filterValue = $api->read('item_sets', $subValue)->getContent()->displayTitle();
                            } catch (NotFoundException $e) {
                                $filterValue = $translate('Unknown item set');
                            }
                            $filters[$filterLabel][] = $filterValue;
                        }
                        break;
                    // Search not item set
                    case 'not_item_set_id':
                        if (!is_array($value)) {
                            $value = [$value];
                        }
                        foreach ($value as $subValue) {
                            if (!is_numeric($subValue)) {
                                continue;
                            }
                            $filterLabel = $translate('Not in item set');
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

                    case 'is_public':
                        $filterLabel = $translate('Visibility');
                        $filters[$filterLabel][] = $value ? $translate('Public') : $translate('Not public');
                        break;

                    case 'has_media':
                        $filterLabel = $translate('Media presence');
                        $filters[$filterLabel][] = $value ? $translate('Has media') : $translate('Has no media');
                        break;

                    case 'id':
                        $filterLabel = $translate('ID');
                        // Avoid a deprecated issue, so convert ids as string.
                        $ids = $value;
                        if (is_int($ids)) {
                            $ids = [(string) $ids];
                        } elseif (is_string($ids)) {
                            $ids = strpos($ids, ',') === false ? [$ids] : explode(',', $ids);
                        } elseif (is_array($ids)) {
                            $ids = array_map('strval', $ids);
                        } else {
                            $ids = [];
                        }
                        $ids = array_map('trim', $ids);
                        $ids = array_filter($ids, 'strlen');
                        $filters[$filterLabel][] = implode(', ', $ids);
                        break;
                }
            }
        }

        $result = $this->getView()->trigger(
            'view.search.filters',
            ['filters' => $filters, 'query' => $query],
            true
        );
        $filters = $result['filters'];

        return $this->getView()->partial(
            $partialName,
            [
                'filters' => $filters,
            ]
        );
    }
}
