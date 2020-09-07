<?php

namespace Omeka\Form\Element;

use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\InputProviderInterface;

class ArrayTextarea extends Textarea implements InputProviderInterface
{
    /**
     * @var bool
     */
    protected $asKeyValue = false;

    /**
     * @var string
     */
    protected $keyValueSeparator = '=';

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (array_key_exists('as_key_value', $this->options)) {
            $this->setAsKeyValue($this->options['as_key_value']);
        }
        if (array_key_exists('key_value_separator', $this->options)) {
            $this->setKeyValueSeparator($this->options['key_value_separator']);
        }
        return $this;
    }

    public function setValue($value)
    {
        $this->value = $this->arrayToString($value);
        return $this;
    }

    public function getInputSpecification()
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

    public function arrayToString($array)
    {
        if (is_string($array)) {
            return $array;
        }
        if ($this->asKeyValue) {
            $string = '';
            foreach ($array as $key => $value) {
                $string .= strlen($value) ? "$key $this->keyValueSeparator $value\n" : $key . "\n";
            }
            return $string;
        }
        return implode("\n", $array);
    }

    public function stringToArray($string)
    {
        if (is_array($string)) {
            return $string;
        }
        return $this->asKeyValue
            ? $this->stringToKeyValues($string)
            : $this->stringToList($string);
    }

    /**
     * Get each line of a string separately as a key-value list.
     *
     * @param string $string
     * @return array
     */
    protected function stringToKeyValues($string)
    {
        $result = [];
        foreach ($this->stringToList($string) as $keyValue) {
            if (strpos($keyValue, $this->keyValueSeparator) === false) {
                $result[trim($keyValue)] = '';
            } else {
                list($key, $value) = array_map('trim', explode($this->keyValueSeparator, $keyValue, 2));
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Get each line of a string separately as a list.
     *
     * @param string $string
     * @return array
     */
    protected function stringToList($string)
    {
        return array_filter(array_map('trim', explode("\n", $this->fixEndOfLine($string))), 'strlen');
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     *
     * @param string $string
     * @return string
     */
    protected function fixEndOfLine($string)
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $string);
    }

    /**
     * Set the option key/value or simple list.
     *
     * @param bool $asKeyValue
     */
    public function setAsKeyValue($asKeyValue)
    {
        $this->asKeyValue = (bool) $asKeyValue;
        return $this;
    }

    /**
     * Get the option as key/value or simple list.
     *
     * @return bool
     */
    public function getAsKeyValue()
    {
        return $this->asKeyValue;
    }

    /**
     * Set the option to separate key and value.
     *
     * @param string $keyValueSeparator
     */
    public function setKeyValueSeparator($keyValueSeparator = '=')
    {
        $this->keyValueSeparator = $keyValueSeparator;
        return $this;
    }

    /**
     * Get the option to separate key and value.
     *
     * @return string
     */
    public function getKeyValueSeparator($separator)
    {
        return $this->keyValueSeparator;
    }
}
