<?php
namespace Omeka\Form\Element;

use Omeka\Api\Exception\NotFoundException;

class ResourceClassSelect extends AbstractVocabularyMemberSelect
{
    public function getResourceName()
    {
        return 'resource_classes';
    }

    /**
     * Get value options for classes.
     *
     * If the "apply_templates" option is set, get only the classes of the
     * configured resource templates.
     * Otherwise get the default value options.
     */
    public function getValueOptions(): array
    {
        $applyTemplates = $this->getOption('apply_templates');
        if (!is_array($applyTemplates)) {
            // Use default method.
            return parent::getValueOptions();
        }

        // Keep original query for further processing.
        $originalQuery = $this->getOption('query');

        try {
            $classIdsOfTemplates = $applyTemplates
                ? $this->getApiManager()->search('resource_templates', ['id' => array_values($applyTemplates)], ['returnScalar' => 'resourceClass'])->getContent()
                : [];
        } catch (NotFoundException $e) {
            $classIdsOfTemplates = [];
        }

        if ($classIdsOfTemplates) {
            $query = $originalQuery;
            if (!is_array($query) || empty($query)) {
                $query = ['id' => $classIdsOfTemplates];
            } elseif (empty($query['id'])) {
                $query['id'] = $classIdsOfTemplates;
            } elseif (is_array($query['id'])) {
                $query['id'] = array_unique(array_merge(array_values($query['id']), $classIdsOfTemplates));
            } else {
                $query['id'] = array_unique(array_merge([$query['id']], $classIdsOfTemplates));
            }
            $this->setOption('query', $query);
        }

        $valueOptions = parent::getValueOptions();

        // The output should be flat and sorted when templates are applied.

        // Flat value options.
        $result = [];
        foreach ($valueOptions as $value => $valueOption) {
            if (is_array($valueOption)) {
                if (array_key_exists('options', $valueOption)) {
                    foreach ($valueOption['options'] ?? [] as $value => $option) {
                        if (is_array($option)) {
                            $result[$option['value']] = $option;
                        } else {
                            $result[$value] = ['value' => $value, 'label' => $option];
                        }
                    }
                } else {
                    $result[$valueOption['value']] = $valueOption;
                }
            } else {
                $result[$value] = ['value' => $value, 'label' => $valueOption];
            }
        }
        $valueOptions = $result;

        // Sort options alphabetically.
        usort($valueOptions, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });

        // Prepend configured value options.
        // Options are already prepended, so just re-set order.
        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = array_replace($prependValueOptions, $valueOptions);
        }

        // The event is already triggered.

        $this->setOption('query', $originalQuery);

        return $valueOptions;
    }
}
