<?php
namespace Omeka\Form\Element;

class ResourceTemplateSelect extends AbstractGroupByOwnerSelect
{
    public function getResourceName()
    {
        return 'resource_templates';
    }

    public function getValueLabel($resource)
    {
        return $resource->label();
    }

    /**
     * Get value options for templates.
     *
     * If the "apply_templates" option is set, get only the configured resource
     * templates.
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

        $applyTemplates = array_values($applyTemplates);

        if ($applyTemplates) {
            $query = $originalQuery;
            if (!is_array($query) || empty($query)) {
                $query = ['id' => $applyTemplates];
            } elseif (empty($query['id'])) {
                $query['id'] = $applyTemplates;
            } elseif (is_array($query['id'])) {
                $query['id'] = array_unique(array_merge(array_values($query['id']), $applyTemplates));
            } else {
                $query['id'] = array_unique(array_merge([$query['id']], $applyTemplates));
            }
        }

        $this->setOption('query', $query);

        $valueOptions = parent::getValueOptions();

        $this->setOption('query', $originalQuery);

        return $valueOptions;
    }
}
