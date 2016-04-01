<?php
namespace Omeka\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\View\Renderer\PhpRenderer;

class Uri extends AbstractDataType
{
    public function getLabel()
    {
        return 'URI';
    }

    public function getTemplate(PhpRenderer $view)
    {
        return $view->partial('common/data-type/uri');
    }

    public function isValid(array $valueObject)
    {
        if (isset($valueObject['@id'])
            && is_string($valueObject['@id'])
            && '' !== trim($valueObject['@id'])
        ) {
             return true;
        }
        return false;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setType($valueObject['type']);
        $value->setValue($valueObject['@id']);
        if (isset($valueObject['o:uri_label'])) {
            $value->setUriLabel($valueObject['o:uri_label']);
        } else {
            $value->setUriLabel(null); // set default
        }
        $value->setLang(null); // set default
        $value->setValueResource(null); // set default
    }

    public function getHtml(PhpRenderer $view, ValueRepresentation $value)
    {
        $uri = $value->value();
        $uriLabel = $value->uriLabel();
        if (!$uriLabel) {
            $uriLabel = $uri;
        }
        return $view->hyperlink($uriLabel, $uri);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        $jsonLd = ['@id' => $value->value()];
        if ($value->uriLabel()) {
            $jsonLd['o:uri_label'] = $value->uriLabel();
        }
        return $jsonLd;
    }
}
