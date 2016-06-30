<?php
namespace Omeka\View\Helper;

use Omeka\Api\Exception\NotFoundException;
use Zend\View\Helper\AbstractHelper;

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
    public function __invoke($partialName = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $translate = $this->getView()->plugin('translate');

        $filters = [];
        $exclude = ['submit', 'page', 'sort_by', 'sort_order', 'resource-type'];
        $api = $this->getView()->api();
        $query = $this->getView()->params()->fromQuery();
        $queryTypes = [
            'eq' => $translate('has exact value(s)'),
            'neq' => $translate('does not have exact value(s)'),
            'in' => $translate('contains value(s)'),
            'nin' => $translate('does not contain value(s)'),
            'res' => $translate('has resource'),
            'nres' => $translate('does not have resource')
        ];

        foreach($query as $key => $value) {

            if ($value != null && in_array($key, $exclude) == false) {
                switch ($key) {

                    // Search by class
                    case 'resource_class_id':
                        $filterLabel = $translate('Resource class');
                        try {
                            $filterValue = $api->read('resource_classes', $value)->getContent()->label();
                        } catch (NotFoundException $e) {
                            $filterValue = $translate('Unknown');
                        }
                        $filters[$filterLabel][] = $filterValue;
                        break;

                    // Search values (by property or all)
                    case 'property':
                        foreach ($value as $propertyRow => $propertyQuery) {
                            if ($propertyRow) {
                                try {
                                    $propertyLabel = $api->read('properties', $propertyRow)->getContent()->label();
                                } catch (NotFoundException $e) {
                                    $propertyLabel = $translate('Unknown property');
                                }
                            } else {
                                $propertyLabel = $translate('[Any property]');
                            }
                            foreach ($propertyQuery as $queryTypeKey => $filterValues) {
                                if (!isset($queryTypes[$queryTypeKey])) {
                                    break;
                                }
                                $filterLabel = $propertyLabel . ' ' . $queryTypes[$queryTypeKey];
                                foreach ($filterValues as $filterValue) {
                                    if (is_string($filterValue) && $filterValue !== '') {
                                        $filters[$filterLabel][] = $filterValue;
                                    }
                                }
                            }
                        }
                        break;

                    // Search resources
                    case 'has_property':
                        foreach ($value as $propertyId => $status) {
                            try {
                                $propertyLabel = $api->read('properties', $propertyId)->getContent()->label();
                            } catch (NotFoundException $e) {
                                $propertyLabel = $translate('Unknown property');
                            }
                            if ($status == 0) {
                                $filterLabel = $translate('Has properties');
                            } else {
                                $filterLabel = $translate('Does not have properties');
                            }
                            $filters[$filterLabel][] = $propertyLabel;
                        }
                        break;

                    // Search resource template
                    case 'resource_template_id':
                            $filterLabel = $translate('Resource Template');
                            try {
                                $filterValue = $api->read('resource_templates', $value)->getContent()->label();
                            } catch (NotFoundException $e) {
                                $filterValue = $translate('Unknown resource template');
                            }
                            $filters[$filterLabel][] = $filterValue;
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
                            $filterLabel = $translate('Item Set');
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

                    default:
                        $filterLabel = ucfirst($key);
                        $filterValue = $value;
                        $filters[$filterLabel][] = $filterValue;
                        break;
                }
            }
        }

        return $this->getView()->partial(
            $partialName,
            [
                'filters'     => $filters
            ]
        );
    }
}
