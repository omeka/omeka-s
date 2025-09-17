<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Form\Element\SelectSortTrait;

/**
 * View helper for rendering the property selector.
 */
class PropertySelector extends AbstractHelper
{
    use SelectSortTrait;

    /**
     * @var string Selector markup cache
     */
    protected $selectorMarkup;

    /**
     * Return the property selector form control.
     *
     * @param string $propertySelectorText
     * @param bool $active
     * @return string
     */
    public function __invoke($propertySelectorText = null, $active = true)
    {
        if ($this->selectorMarkup) {
            // Build the selector markup only once.
            return $this->selectorMarkup;
        }

        $view = $this->getView();

        $vocabResponse = $this->getView()->api()->search('vocabularies');
        $propResponse = $this->getView()->api()->search('properties', ['limit' => 0]);

        // Build the vocabulary properties array.
        $options = [];
        foreach ($vocabResponse->getContent() as $vocabulary) {
            $options[$vocabulary->prefix()] = [
                'label' => $view->translate($vocabulary->label()),
                'vocabulary' => $vocabulary,
                'options' => [],
            ];
            foreach ($vocabulary->properties() as $property) {
                $options[$vocabulary->prefix()]['options'][] = [
                    'label' => $view->translate($property->label()),
                    'property' => $property,
                ];
            }
        }
        $options = $this->sortSelectOptions($options);

        return $view->partial(
            'common/property-selector',
            [
                'options' => $options,
                'totalCount' => $propResponse->getTotalResults(),
                'propertySelectorText' => $propertySelectorText,
                'state' => $active ? 'always-open' : '',
            ]
        );
    }
}
