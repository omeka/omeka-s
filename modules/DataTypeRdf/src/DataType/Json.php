<?php declare(strict_types=1);

namespace DataTypeRdf\DataType;

use Laminas\Form\Element;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;

/**
 * Not yet standard (working draft rdf v1.2).
 *
 * @link https://www.w3.org/TR/rdf12-concepts/#section-json
 */
class Json extends AbstractDataTypeRdf
{
    public function getName()
    {
        return 'json';
    }

    public function getLabel()
    {
        return 'Json';
    }

    public function prepareForm(PhpRenderer $view): void
    {
        $plugins = $view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');

        $view->headLink()
            ->appendStylesheet($assetUrl('vendor/codemirror/lib/codemirror.css', 'DataTypeRdf'))
            ->appendStylesheet($assetUrl('vendor/codemirror/addon/hint/show-hint.css', 'DataTypeRdf'));
        $view->headScript()
            ->appendFile($assetUrl('vendor/codemirror/lib/codemirror.js', 'DataTypeRdf'))
            // ->appendFile($assetUrl('vendor/codemirror/addon/display/placeholder.js', 'DataTypeRdf'))
            // Required in resource form.
            ->appendFile($assetUrl('vendor/codemirror/addon/display/autorefresh.js', 'DataTypeRdf'))
            ->appendFile($assetUrl('vendor/codemirror/addon/edit/closebrackets.js', 'DataTypeRdf'))
            ->appendFile($assetUrl('vendor/codemirror/addon/edit/matchbrackets.js', 'DataTypeRdf'))
            ->appendFile($assetUrl('vendor/codemirror/addon/edit/trailingspace.js', 'DataTypeRdf'))
            ->appendFile($assetUrl('vendor/codemirror/addon/hint/javascript-hint.js', 'DataTypeRdf'))
            // ->appendFile($assetUrl('vendor/codemirror/addon/lint/json-lint.js', 'DataTypeRdf'))
            ->appendFile($assetUrl('vendor/codemirror/mode/javascript/javascript.js', 'DataTypeRdf'))
            ->appendFile($assetUrl('js/data-type-rdf.js', 'DataTypeRdf'), 'text/javascript', ['defer' => 'defer']);
    }

    public function form(PhpRenderer $view)
    {
        $element = new Element\Textarea('json');
        $element->setAttributes([
            'class' => 'value to-require json json-edit',
            'data-value-key' => '@value',
            /*
            'placeholder' => '{"alpha": "beta"}',
            */
        ]);
        return $view->formTextarea($element);
    }

    public function isValid(array $valueObject)
    {
        return isset($valueObject['@value'])
            && (trim($valueObject['@value']) === 'null' || json_decode($valueObject['@value'], true) !== null);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter): void
    {
        $value->setValue(trim((string) $valueObject['@value']));
        // Set defaults.
        // No language for json.
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value, $options = [])
    {
        $options = (array) $options;
        return empty($options['raw'])
            ? (string) $value->value()
            : $view->escapeHtml($value->value());
    }

    /**
     * Only scalar json is returned.
     *
     * {@inheritDoc}
     * @see \Omeka\DataType\AbstractDataType::getFulltextText()
     */
    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        $json = (string) $value->value();
        if ($json === 'null') {
            return '';
        }
        $json = json_decode($json, true);
        return is_scalar($json)
            ? (string) $json
            : '';
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return [
            '@value' => $value->value(),
            '@type' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#JSON',
        ];
    }
}
