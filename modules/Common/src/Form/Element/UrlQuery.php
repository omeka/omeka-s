<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputProviderInterface;

class UrlQuery extends Text implements InputProviderInterface
{
    public function setValue($value)
    {
        $this->value = $this->arrayToQuery($value);
        return $this;
    }

    public function getInputSpecification(): array
    {
        return [
            'name' => $this->getName(),
            'required' => false,
            'allow_empty' => true,
            'filters' => [
                [
                    'name' => \Laminas\Filter\Callback::class,
                    'options' => [
                        'callback' => [$this, 'queryToArray'],
                    ],
                ],
            ],
        ];
    }

    public function arrayToQuery($array): string
    {
        return is_string($array)
            ? $array
            : http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    public function queryToArray($string): array
    {
        if (is_array($string)) {
            return $string;
        }
        $query = [];
        parse_str(ltrim((string) $string, "? \t\n\r\0\x0B"), $query);
        return $query;
    }
}
