<?php
namespace Omeka\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class Uri extends AbstractDataType implements ValueAnnotatingInterface
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
        if (!isset($valueObject['@id'])
            || !is_string($valueObject['@id'])
        ) {
            return false;
        }

        $trimmed = trim($valueObject['@id']);
        $scheme = parse_url($trimmed, \PHP_URL_SCHEME);

        return !('' === $trimmed || $scheme === 'javascript');
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setUri($valueObject['@id']);
        if (isset($valueObject['o:label'])) {
            $value->setValue($valueObject['o:label']);
        } else {
            $value->setValue(null); // set default
        }
        $value->setLang(null); // set default
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
}
