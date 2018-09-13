<?php
namespace Omeka\Form\Element;

use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Manager as ApiManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Form\Element\Select;

abstract class AbstractVocabularyMemberSelect extends Select implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @var ApiManager
     */
    protected $apiManager;

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }

    /**
     * Get the resource name.
     *
     * @return string
     */
    abstract public function getResourceName();

    public function getValueOptions()
    {
        $events = $this->getEventManager();

        $resourceName = $this->getResourceName();

        $termAsValue = $this->getOption('term_as_value');
        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'label';
        }
        $applyTemplates = $this->getOption('apply_templates');
        $applyTemplates = is_array($applyTemplates) ? $applyTemplates : false;

        if ('properties' === $resourceName && $applyTemplates) {
            // Apply resource templates to property select by only including
            // properties from the passed templates.
            $valueOptions = [];
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
        } else {
            // Default vocabulary member value options.
            $args = $events->prepareArgs(['query' => $query]);
            $events->trigger('form.vocab_member_select.query', $this, $args);
            $query = $args['query'];

            $valueOptions = [];
            $response = $this->getApiManager()->search($resourceName, $query);
            foreach ($response->getContent() as $member) {
                $attributes = ['data-term' => $member->term()];
                if ('properties' === $resourceName) {
                    $attributes['data-property-id'] = $member->id();
                } elseif ('resource_classes' === $resourceName) {
                    $attributes['data-resource-class-id'] = $member->id();
                }
                $option = [
                    'label' => $member->label(),
                    'value' => $termAsValue ? $member->term() : $member->id(),
                    'attributes' => $attributes,
                ];
                $vocabulary = $member->vocabulary();
                if (!isset($valueOptions[$vocabulary->prefix()])) {
                    $valueOptions[$vocabulary->prefix()] = [
                        'label' => $vocabulary->label(),
                        'options' => [],
                    ];
                }
                $valueOptions[$vocabulary->prefix()]['options'][] = $option;
            }
            // Move Dublin Core vocabularies (dcterms & dctype) to the beginning.
            if (isset($valueOptions['dcterms'])) {
                $valueOptions = ['dcterms' => $valueOptions['dcterms']] + $valueOptions;
            }
            if (isset($valueOptions['dctype'])) {
                $valueOptions = ['dctype' => $valueOptions['dctype']] + $valueOptions;
            }
        }

        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }

        $args = $events->prepareArgs(['valueOptions' => $valueOptions]);
        $events->trigger('form.vocab_member_select.value_options', $this, $args);
        $valueOptions = $args['valueOptions'];

        return $valueOptions;
    }
}
