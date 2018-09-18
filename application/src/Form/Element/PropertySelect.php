<?php
namespace Omeka\Form\Element;

use Omeka\Api\Exception\NotFoundException;

class PropertySelect extends AbstractVocabularyMemberSelect
{
    public function getResourceName()
    {
        return 'properties';
    }

    /**
     * Get value options for properties.
     *
     * If the "apply_templates" option is set, get only the properties of the
     * configured resource templates and include alternate labels, if any.
     * Otherwise get the default value options.
     *
     * @return array
     */
    public function getValueOptions()
    {
        $applyTemplates = $this->getOption('apply_templates');
        $applyTemplates = is_array($applyTemplates) ? $applyTemplates : false;
        if (!$applyTemplates) {
            // Use default method.
            return parent::getValueOptions();
        }
        // Get only the properties of the configured resource templates.
        $valueOptions = [];
        $termAsValue = $this->getOption('term_as_value');
        foreach ($applyTemplates as $templateId) {
            try {
                $template = $this->getApiManager()->read('resource_templates', $templateId)->getContent();
            } catch (NotFoundException $e) {
                continue;
            }
            foreach ($template->resourceTemplateProperties() as $templateProperty) {
                $property = $templateProperty->property();
                if (!isset($valueOptions[$property->id()])) {
                    $valueOptions[$property->id()] = [
                        'label' => $property->label(),
                        'value' => $termAsValue ? $property->term() : $property->id(),
                        'alternate_labels' => [],
                    ];
                }
                $valueOptions[$property->id()]['alternate_labels'][] = $templateProperty->alternateLabel();
            }
        }
        // Include alternate labels, if any.
        foreach ($valueOptions as $propertyId => $option) {
            $altLabels = array_unique(array_filter($valueOptions[$propertyId]['alternate_labels']));
            if ($altLabels) {
                $valueOptions[$propertyId]['label'] = sprintf(
                    '%s: %s',
                    $valueOptions[$propertyId]['label'],
                    implode('; ', $altLabels)
                );
            }
        }
        // Sort options alphabetically.
        usort($valueOptions, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        return $valueOptions;
    }
}
