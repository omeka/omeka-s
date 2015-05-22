<?php
namespace Omeka\View\Helper;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class SearchFilters extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/search-filters';

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->request = $serviceLocator->get('Request');
    }

    /**
     * Render filters from search query.
     *
     * @return array
     */
    function __invoke($partialName = null) 
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $translate = $this->getView()->plugin('translate');

        $filters = array();
        $exclude = array('submit', 'page', 'sort_by', 'sort_order', 'resource-type');
        $api = $this->getView()->api();
        $query = $this->request->getQuery()->toArray();
        $queryTypes = array(
            'eq' => $translate('has exact value(s)'),
            'neq' => $translate('does not have exact value(s)'),
            'in' => $translate('contains value(s)'),
            'nin' => $translate('does not contain value(s)')
        );
        
        foreach($query as $key => $value) {
        
            if ($value != null && in_array($key, $exclude) == false) {
                $filterLabel = ucfirst($key);
                $filterValue = null;
                switch ($key) {
        
                    // Search by class
                    case 'resource_class_id':
                        $filterLabel = 'Resource class';
                        $filterValue = $api->read('resource_classes', $value, array('label'))->getContent()->label();
                        $filters[$filterLabel][] = $filterValue;
                        break;
        
                    // Search all properties
                    case 'value':
                        foreach ($value as $queryTypeKey => $filterValues) {
                            $filterLabel = $translate('Property ') . ' ' . $queryTypes[$queryTypeKey];
                            foreach ($filterValues as $filterValue) {
                                $filters[$filterLabel][] = $filterValue;
                            }
                        }
                        break;
        
                    // Search specific property
                    case 'property':
                        foreach ($value as $propertyRow => $propertyQuery) {
                            $propertyLabel = $api->read('properties', $propertyRow, array('label'))->getContent()->label();
                            foreach ($propertyQuery as $queryTypeKey => $filterValues) {
                                $filterLabel = $propertyLabel . ' ' . $queryTypes[$queryTypeKey];
                                foreach ($filterValues as $filterValue) {
                                    $filters[$filterLabel][] = $filterValue;
                                }
                            }
                        }
                        break;
        
                    // Search resources
                    case 'has_property':
                        foreach ($value as $propertyId => $status) {
                            $propertyLabel = $api->read('properties', $propertyId, array('label'))->getContent()->label();
                            if ($status == 0) {
                                $filterLabel = $translate('Has properties');
                            } else {
                                $filterLabel = $translate('Does not have properties');
                            }
                            $filters[$filterLabel][] = $propertyLabel;
                        }
                        break;

                    default:
                        $filters[$filterLabel][] = $filterValue;
                        break;
                }
            }
        }

        return $this->getView()->partial(
            $partialName,
            array(
                'filters'     => $filters
            )
        );
    }
}
?>