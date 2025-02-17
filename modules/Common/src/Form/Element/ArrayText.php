<?php declare(strict_types=1);

namespace Common\Form\Element;

use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputProviderInterface;

class ArrayText extends Text implements InputProviderInterface
{
    /**
     * @var string
     */
    protected $valueSeparator = '=';

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (array_key_exists('value_separator', $this->options)) {
            $this->setValueSeparator($this->options['value_separator']);
        }
        return $this;
    }

    public function setValue($value)
    {
        $this->value = $this->arrayToString($value);
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
                        'callback' => [$this, 'stringToArray'],
                    ],
                ],
            ],
        ];
    }

    public function arrayToString($array): string
    {
        return is_string($array)
            ? $array
            : implode($this->valueSeparator, $array);
    }

    public function stringToArray($string): array
    {
        if (is_array($string)) {
            return $string;
        }
        // Warning: explode('=', '') is not an empty array.
        $string = trim((string) $string);
        return strlen($string)
            ? array_map('trim', explode($this->valueSeparator, $string))
            : [];
    }

    /**
     * Set the option to separate value.
     */
    public function setValueSeparator(string $valueSeparator = '='): self
    {
        $this->valueSeparator = $valueSeparator;
        return $this;
    }

    /**
     * Get the option to separate key and value.
     */
    public function getValueSeparator(): string
    {
        return $this->valueSeparator;
    }
}
