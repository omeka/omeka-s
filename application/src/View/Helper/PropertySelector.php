<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering the property selector.
 */
class PropertySelector extends AbstractHelper
{
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
        $vocabProps = [];
        foreach ($vocabResponse->getContent() as $vocabulary) {
            $vocabProps[$vocabulary->prefix()] = [
                'label' => $view->translate($vocabulary->label()),
                'vocabulary' => $vocabulary,
                'properties' => [],
            ];
            foreach ($vocabulary->properties() as $property) {
                $vocabProps[$vocabulary->prefix()]['properties'][] = [
                    'label' => $view->translate($property->label()),
                    'property' => $property,
                ];
            }
        }
        // Sort vocabulary labels alphabetically.
        uasort($vocabProps, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        // Sort member labels alphabetically.
        foreach ($vocabProps as &$vocabProp) {
            uasort($vocabProp['properties'], function ($a, $b) {
                return strcasecmp($a['label'], $b['label']);
            });
        }

        return $this->getView()->partial(
            'common/property-selector',
            [
                'vocabProps' => $vocabProps,
                'totalPropertyCount' => $propResponse->getTotalResults(),
                'propertySelectorText' => $propertySelectorText,
                'state' => $active ? 'always-open' : '',
            ]
        );
    }
}
