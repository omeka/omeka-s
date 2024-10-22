<?php
namespace Omeka\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class Literal extends AbstractDataType implements ValueAnnotatingInterface, ConvertableInterface
{
    public function getName()
    {
        return 'literal';
    }

    public function getLabel()
    {
        return 'Text'; // @translate
    }

    public function form(PhpRenderer $view)
    {
        return $view->partial('common/data-type/literal');
    }

    public function isValid(array $valueObject)
    {
        return $this->literalIsValid($valueObject['@value'] ?? null);
    }

    public function literalIsValid($literal)
    {
        return (is_string($literal) && '' !== trim($literal));
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue($valueObject['@value']);
        if (isset($valueObject['@language'])) {
            $value->setLang($valueObject['@language']);
        } else {
            $value->setLang(null); // set default
        }
        $value->setUri(null); // set default
        $value->setValueResource(null); // set default
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return nl2br($view->escapeHtml($value->value()));
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        $jsonLd = ['@value' => $value->value()];
        if ($value->lang()) {
            $jsonLd['@language'] = $value->lang();
        }
        return $jsonLd;
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $view->partial('common/data-type/value-annotation-literal');
    }

    public function convert(Value $valueObject, string $dataTypeName)
    {
        $value = $valueObject->getValue();
        $uri = $valueObject->getUri();

        // Convert all data types to literal.
        $valueObject->setType($this->getName());

        // For value entities with a URI but no value, move the URI to the value.
        if (!$this->literalIsValid($value) && $this->literalIsValid($uri)) {
            $valueObject->setValue($uri);
            $valueObject->setUri(null);
        }
    }
}
