<?php
namespace Omeka\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class Uri extends AbstractDataType implements ValueAnnotatingInterface, ConversionTargetInterface
{
    public function getName()
    {
        return 'uri';
    }

    public function getLabel()
    {
        return 'URI'; // @translate
    }

    public function form(PhpRenderer $view)
    {
        return $view->partial('common/data-type/uri');
    }

    public function isValid(array $valueObject)
    {
        return $this->uriIsValid($valueObject['@id'] ?? null);
    }

    public function uriIsValid($uri)
    {
        if (!is_string($uri)) {
            return false;
        }
        $uri = trim($uri);
        $scheme = parse_url($uri, \PHP_URL_SCHEME);
        return !('' === $uri || 'javascript' === $scheme);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setUri($valueObject['@id']);
        if (isset($valueObject['o:label'])) {
            $value->setValue($valueObject['o:label']);
        } else {
            $value->setValue(null); // set default
        }
        $value->setLang($valueObject['o:lang'] ?? null); // set default
        $value->setValueResource(null); // set default
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        $uri = $value->uri();
        $uriLabel = $value->value();
        if (!$uriLabel) {
            $uriLabel = $uri;
        }
        return $view->hyperlink($uriLabel, $uri, ['class' => 'uri-value-link', 'target' => '_blank']);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        $jsonLd = ['@id' => $value->uri()];
        if ($value->value()) {
            $jsonLd['o:label'] = $value->value();
        }
        if ($value->lang()) {
            $jsonLd['o:lang'] = $value->lang();
        }
        return $jsonLd;
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return sprintf('%s %s', $value->uri(), $value->value());
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $view->partial('common/data-type/value-annotation-uri');
    }

    public function convert(Value $valueObject, string $dataTypeTarget): bool
    {
        $value = $valueObject->getValue();
        $uri = $valueObject->getUri();

        if ($this->uriIsValid($uri)) {
            return true;
        }
        if ($this->uriIsValid($value)) {
            // Move the value to the URI.
            $valueObject->setUri($value);
            $valueObject->setValue(null);
            return true;
        }
        return false;
    }
}
