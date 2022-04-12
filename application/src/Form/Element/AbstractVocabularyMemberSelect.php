<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Element\Select;
use Laminas\I18n\Translator\TranslatorAwareTrait;

abstract class AbstractVocabularyMemberSelect extends Select implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;
    use TranslatorAwareTrait;

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

    /**
     * Get default value options for this vocabulary member.
     *
     * @return array
     */
    public function getValueOptions()
    {
        $events = $this->getEventManager();
        $resourceName = $this->getResourceName();

        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'label';
        }
        // Allow handlers to filter the query.
        $args = $events->prepareArgs(['query' => $query]);
        $events->trigger('form.vocab_member_select.query', $this, $args);
        $query = $args['query'];

        $valueOptions = [];
        $response = $this->getApiManager()->search($resourceName, $query);
        $termAsValue = $this->getOption('term_as_value');
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

        // Prepend configured value options.
        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }

        // Allow handlers to filter the value options.
        $args = $events->prepareArgs(['valueOptions' => $valueOptions]);
        $events->trigger('form.vocab_member_select.value_options', $this, $args);
        $valueOptions = $args['valueOptions'];

        return $valueOptions;
    }
}
