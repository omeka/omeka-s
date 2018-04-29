<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering search user filters.
 */
class SearchUserFilters extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/search-filters';

    /**
     * @var array
     */
    protected $roleLabels;

    public function __construct(array $roleLabels)
    {
        $this->roleLabels = $roleLabels;
    }

    /**
     * Render filters from search query.
     *
     * @see SearchFilters::__invoke()
     * @return array
     */
    public function __invoke($partialName = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $translate = $this->getView()->plugin('translate');

        $filters = [];
        $api = $this->getView()->api();
        $query = $this->getView()->params()->fromQuery();

        foreach ($query as $key => $value) {
            if (!strlen($value)) {
                continue;
            }

            switch ($key) {
                case 'email':
                    $filterLabel = $translate('Email');
                    $filters[$filterLabel][] = $value;
                    break;

                case 'name':
                    $filterLabel = $translate('Name');
                    $filters[$filterLabel][] = $value;
                    break;

                case 'role':
                    $filterLabel = $translate('Role');
                    $filters[$filterLabel][] = isset($this->roleLabels[$value])
                        ? $this->roleLabels[$value]
                        : $translate('Unknown role');
                    break;

                case 'is_active':
                    $filterLabel = $translate('Is active');
                    $filters[$filterLabel][] = $value ? $translate('yes') : $translate('no');
                    break;

                case 'site_permission_site_id':
                    $filterLabel = $translate('Has permission in site');
                    try {
                        $filterValue = $api->read('sites', $value)->getContent()->title();
                    } catch (NotFoundException $e) {
                        $filterValue = $translate('Unknown site');
                    }
                    $filters[$filterLabel][] = $filterValue;
                    break;
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
