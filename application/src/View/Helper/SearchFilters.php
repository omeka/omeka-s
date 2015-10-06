<?php
namespace Omeka\View\Helper;

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
                $filterLabel = ucfirst($key);
                $filterValue = null;
                switch ($key) {
        
                    // Search by class
                    case 'resource_class_id':
                        $filterLabel = 'Resource class';
                        $filterValue = $api->read('resource_classes', $value, ['label'])->getContent()->label();
                        $filters[$filterLabel][] = $filterValue;
                        break;
        
                    // Search all properties
                    case 'value':
                        foreach ($value as $queryTypeKey => $filterValues) {
                            if (!isset($queryTypes[$queryTypeKey])) {
                                break;
                            }
                            $filterLabel = $translate('Property ') . ' ' . $queryTypes[$queryTypeKey];
                            foreach ($filterValues as $filterValue) {
                                if (is_string($filterValue) && $filterValue !== '') {
                                    $filters[$filterLabel][] = $filterValue;
                                }
                            }
                        }
                        break;
        
                    // Search specific property
                    case 'property':
                        foreach ($value as $propertyRow => $propertyQuery) {
                            $propertyLabel = $api->read('properties', $propertyRow, ['label'])->getContent()->label();
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
                            $propertyLabel = $api->read('properties', $propertyId, ['label'])->getContent()->label();
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
                            $filterValue = $api->read('resource_templates', $value, ['label'])->getContent()->label();
                            $filters[$filterLabel][] = $filterValue;
                        break;

                    // Search item set
                    case 'item_set_id':
                        $filterLabel = $translate('Item Set');
                        $filterValue = $api->read('item_sets', $value, ['label'])->getContent()->displayTitle();
                        $filters[$filterLabel][] = $filterValue;
                        break;

                    // Search user
                    case 'owner_id':
                        $filterLabel = $translate('User');
                        $filterValue = $api->read('users', $value, ['label'])->getContent()->name();
                        $filters[$filterLabel][] = $filterValue;
                        break;

                    default:
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
