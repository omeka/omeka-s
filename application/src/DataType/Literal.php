<?php
namespace Omeka\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class Literal extends AbstractDataType implements ValueAnnotatingInterface, ConversionTargetInterface
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

    public function convert(Value $valueObject, string $dataTypeTarget): bool
    {
        $value = $valueObject->getValue();
        $uri = $valueObject->getUri();

        // Note that, in order to prevent data loss, we do not convert if a URI
        // and value are present.
        if ($this->literalIsValid($uri) && !$this->literalIsValid($value)) {
            $valueObject->setValue($uri);
            $valueObject->setUri(null);
            return true;
        }
        if ($this->literalIsValid($value) && !$this->literalIsValid($uri)) {
            return true;
        }
        return false;
    }
}
