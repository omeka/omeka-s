<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Form\Element\ArrayTextarea;

class DataTextarea extends ArrayTextarea
{
    /**
     * @var array
     */
    protected $dataKeys = [];

    /**
     * @var array
     */
    protected $dataArrayKeys = [];

    /**
     * @var array
     */
    protected $dataAssociativeKeys = [];

    /**
     * @var array
     */
    protected $dataOptions = [];

    /**
     * @var string
     *
     * May be "by_line" (one line by data, default) or "last_is_list".
     */
    protected $dataTextMode = '';

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (array_key_exists('data_keys', $this->options)) {
            $this->setDataKeys($this->options['data_keys']);
        }
        if (array_key_exists('data_array_keys', $this->options)) {
            $this->setDataArrayKeys($this->options['data_array_keys']);
        }
        if (array_key_exists('data_options', $this->options)) {
            $this->setDataOptions($this->options['data_options']);
        }
        if (array_key_exists('data_text_mode', $this->options)) {
            $this->setDataTextMode($this->options['data_text_mode']);
        }
        return $this;
    }

    public function arrayToString($array): string
    {
        if (is_string($array)) {
            return $array;
        } elseif (is_null($array)) {
            return '';
        }
        $textMode = $this->getDataTextMode();
        if ($textMode === 'last_is_list') {
            return $this->arrayToStringLastIsList($array);
        }
        return $this->arrayToStringByLine($array);
    }

    public function stringToArray($string): array
    {
        if (is_array($string)) {
            return $string;
        } elseif (is_null($string)) {
            return [];
        }
        $textMode = $this->getDataTextMode();
        if ($textMode === 'last_is_list') {
            return $this->stringToArrayLastIsList((string) $string);
        }
        return $this->stringToArrayByLine((string) $string);
    }

    /**
     * Set the ordered list of keys to use for each line.
     *
     * This option allows to get an associative array instead of a simple list
     * for each row.
     *
     * Each specified key will be used as the keys of each part of each line.
     * There is no default keys: in that case, the values are a simple array of
     * array.
     * With option "as_key_value", the first value will be the used as key for
     * the main array too.
     *
     * @example When passing options to an element:
     * ```php
     *     'data_keys' => [
     *         'field',
     *         'label',
     *         'type',
     *         'options',
     *     ],
     * ```
     *
     * @deprecated Use setDataOptions() instead.
     */
    public function setDataKeys(array $dataKeys)
    {
        $this->dataKeys = array_fill_keys($dataKeys, null);
        return $this;
    }

    /**
     * Get the list of data keys.
     *
     * The data keys are the name of each value of a row in order to get an
     * associative array instead of a simple list.
     *
     * @deprecated Use getDataOptions() instead.
     */
    public function getDataKeys(): array
    {
        return array_keys($this->dataKeys);
    }

    /**
     * Set the option to separate values into multiple values.
     *
     * This option allows to create an array for specific values of the row.
     * Each value of the row can have its own separator.
     *
     * The keys should be a subset of the data keys, so they must be defined.
     *
     * It is not recommended to set the first key when option "as_key_value" is
     *  set. In that case, the whole value is used as key before to be splitted.
     *
     *  This option as no effect for last key when option "last_is_list" is set.
     *
     * @example When passing options to an element:
     * ```php
     *     'data_array_keys' => [
     *         'options' => '|',
     *     ],
     * ```
     *
     *  @deprecated Use setDataOptions() instead.
     */
    public function setDataArrayKeys(array $dataArrayKeys)
    {
        $this->dataArrayKeys = $dataArrayKeys;
        return $this;
    }

    /**
     * Get the option to separate values into multiple values.
     *
     *  @deprecated Use getDataOptions() instead.
     */
    public function getDataArrayKeys(): array
    {
        return $this->dataArrayKeys;
    }

    /**
     * Set the ordered list of keys to use for each line and their options.
     *
     * This option allows to get an associative array instead of a simple list
     * for each row and to specify options for each of them.
     * Managed sub-options are:
     * - separator (string): allow to explode the string to create a sub-array
     * - associative (string): allow to create an associative sub-array. This
     *   option as no effect for last key when option "last_is_list" is set.
     * When the value is a string, it's the separator used to get the sub-array.
     *
     * Each specified key will be used as the keys of each part of each line.
     * There is no default keys: in that case, the values are a simple array of
     * array.
     * With option "as_key_value", the first value will be the used as key for
     * the main array too.
     *
     * @example When passing options to an element:
     * ```php
     *     'data_options' => [
     *         'field' => null,
     *         'label' => null,
     *         'type' => null,
     *         'options' => [
     *             'separator' => '|',
     *             'associative' => '=',
     *         ],
     *     ],
     * ```
     * @todo Allow data_options for sub-options to get associative sub-array automatically.
     */
    public function setDataOptions(array $dataOptions)
    {
        $this->dataOptions = $dataOptions;
        // TODO For compatibility as long as code is not updated to use dataOptions.
        $this->dataKeys = array_fill_keys(array_keys($dataOptions), null);
        $arrayKeys = [];
        $associativeKeys = [];
        foreach (array_filter($dataOptions) as $key => $value) {
            if (is_array($value)) {
                if (isset($value['separator'])) {
                    $arrayKeys[$key] = (string) $value['separator'];
                }
                if (isset($value['associative'])) {
                    $associativeKeys[$key] = (string) $value['associative'];
                }
            } elseif (is_scalar($value)) {
                $arrayKeys[$key] = $value;
            }
        }

        $this->dataArrayKeys = $arrayKeys;
        $this->dataAssociativeKeys = $associativeKeys;
        return $this;
    }

    public function getDataOptions(): array
    {
        return $this->dataOptions;
    }

    /**
     * Set the mode to display the text inside the textarea input.
     *
     * - "by_line" (default: all the data are on the same line):
     * ```
     * x = y = z = a | b | c
     * ```
     *
     * - "last_is_list" (the last field is exploded and an empty line is added),
     *   allowing to create groups:
     * ```
     * x = y = z
     * a
     * b
     * c
     *
     * ```
     */
    public function setDataTextMode(?string $dataTextMode)
    {
        $this->dataTextMode = (string) $dataTextMode;
        return $this;
    }

    /**
     * Get the text mode of the data.
     */
    public function getDataTextMode(): string
    {
        return $this->dataTextMode;
    }

    protected function arrayToStringByLine(array $array): string
    {
        // Reorder values according to specified keys and fill empty values.
        $string = '';
        $countDataKeys = count($this->dataKeys);
        // Associative array.
        if ($countDataKeys) {
            $arrayKeys = array_intersect_key($this->dataArrayKeys, $this->dataKeys);
            foreach ($array as $values) {
                if (!is_array($values)) {
                    $values = (array) $values;
                }
                $data = array_replace($this->dataKeys, $values);
                // Manage sub-values.
                foreach ($arrayKeys as $arrayKey => $arraySeparator) {
                    $separator = ' ' . $arraySeparator . ' ';
                    $list = array_map('strval', isset($data[$arrayKey]) ? (array) $data[$arrayKey] : []);
                    if (isset($this->dataAssociativeKeys[$arrayKey]) && !$this->arrayIsList($list)) {
                        $subSeparator = ' ' . $this->dataAssociativeKeys[$arrayKey] . ' ';
                        $kvList = [];
                        foreach ($list as $k => $v) {
                            $kvList[] = $k . $subSeparator . $v;
                        }
                        $data[$arrayKey] = implode($separator, $kvList);
                    } else {
                        $data[$arrayKey] = implode($separator, $list);
                    }
                }
                $string .= implode(' ' . $this->keyValueSeparator . ' ', array_map('strval', $data)) . "\n";
            }
        }
        // Simple list.
        else {
            foreach ($array as $values) {
                if (!is_array($values)) {
                    $values = (array) $values;
                }
                $data = array_values($values);
                $string .= implode(' ' . $this->keyValueSeparator . ' ', array_map('strval', $data)) . "\n";
            }
        }
        $string = rtrim($string, "\n");
        return strlen($string) ? $string . "\n" : '';
    }

    protected function arrayToStringLastIsList(array $array): string
    {
        // Reorder values according to specified keys and fill empty values.
        $string = '';
        $countDataKeys = count($this->dataKeys);
        // Associative array.
        if ($countDataKeys) {
            // Without last key, the result is the same than by line.
            $lastKey = key(array_slice($this->dataKeys, -1));
            $arrayKeys = array_intersect_key($this->dataArrayKeys, $this->dataKeys);
            if (!isset($arrayKeys[$lastKey])) {
                return $this->arrayToStringByLine($array);
            }
            foreach ($array as $values) {
                if (!is_array($values)) {
                    $values = (array) $values;
                }
                $data = array_replace($this->dataKeys, $values);
                // Manage sub-values.
                foreach ($arrayKeys as $arrayKey => $arraySeparator) {
                    $isLastKey = $arrayKey === $lastKey;
                    $separator = $isLastKey ? "\n" : ' ' . $arraySeparator . ' ';
                    $list = array_map('strval', isset($data[$arrayKey]) ? (array) $data[$arrayKey] : []);
                    if (isset($this->dataAssociativeKeys[$arrayKey]) && !$this->arrayIsList($list)) {
                        $subSeparator = ' ' . $this->dataAssociativeKeys[$arrayKey] . ' ';
                        $kvList = [];
                        foreach ($list as $k => $v) {
                            $kvList[] = $k . $subSeparator . $v;
                        }
                        $data[$arrayKey] = implode($separator, $kvList);
                    } else {
                        $data[$arrayKey] = implode($separator, $list);
                    }
                }
                // Don't add the key value separator for the last field, and
                // append a line break to add an empty line.
                $string .= implode(' ' . $this->keyValueSeparator . ' ', array_map('strval', array_slice($data, 0, -1))) . "\n"
                    . $data[$lastKey] . "\n\n";
            }
        }
        // Simple list.
        else {
            foreach ($array as $values) {
                if (!is_array($values)) {
                    $values = (array) $values;
                }
                $data = array_values($values);
                $string .= implode("\n", array_map('strval', $data)) . "\n\n";
            }
        }
        $string = rtrim($string, "\n");
        return strlen($string) ? $string . "\n" : '';
    }

    protected function stringToArrayByLine(string $string): array
    {
        $array = [];
        $countDataKeys = count($this->dataKeys);
        if ($countDataKeys) {
            $arrayKeys = array_intersect_key($this->dataArrayKeys, $this->dataKeys);
            $list = $this->stringToList($string);
            foreach ($list as $values) {
                $values = array_map('trim', explode($this->keyValueSeparator, $values, $countDataKeys));
                // Add empty missing values. The number cannot be higher.
                // TODO Use substr_count() if quicker.
                $missing = $countDataKeys - count($values);
                if ($missing) {
                    $values = array_merge($values, array_fill(0, $missing, ''));
                }
                $data = array_combine(array_keys($this->dataKeys), $values);
                // Manage sub-values.
                foreach ($arrayKeys as $arrayKey => $arraySeparator) {
                    $data[$arrayKey] = $data[$arrayKey] === ''
                        ? []
                        : array_map('trim', explode($arraySeparator, $data[$arrayKey]));
                    if ($data[$arrayKey] && isset($this->dataAssociativeKeys[$arrayKey])) {
                        $asso = [];
                        foreach ($data[$arrayKey] as $k => $v) {
                            if (strpos($v, $this->dataAssociativeKeys[$arrayKey]) !== false) {
                                [$kk, $vv] = array_map('trim', explode($this->dataAssociativeKeys[$arrayKey], $v, 2));
                                $asso[$kk] = $vv;
                            } else {
                                $asso[$k] = $v;
                            }
                        }
                        $data[$arrayKey] = $asso;
                    }
                }
                $this->asKeyValue
                    ? $array[reset($data)] = $data
                    : $array[] = $data;
            }
        } else {
            $list = $this->stringToList($string);
            foreach ($list as $values) {
                // No keys: a simple list.
                $data = array_map('trim', explode($this->keyValueSeparator, $values));
                $this->asKeyValue
                    ? $array[reset($data)] = $data
                    : $array[] = $data;
            }
        }
        return $array;
    }

    protected function stringToArrayLastIsList(string $string): array
    {
        $array = [];
        $countDataKeys = count($this->dataKeys);
        if ($countDataKeys) {
            // Without last key, the result is the same than by line.
            $lastKey = key(array_slice($this->dataKeys, -1));
            $arrayKeys = array_intersect_key($this->dataArrayKeys, $this->dataKeys);
            if (!isset($arrayKeys[$lastKey])) {
                return $this->stringToArrayByLine($array);
            }
            // Create groups from empty lines, namely a double line break.
            $groups = array_filter(array_map('trim', explode("\n\n", $this->fixEndOfLine($string))));
            foreach ($groups as $group) {
                $values = array_map('trim', explode("\n", $group));
                $firstFieldsValues = array_map('trim', explode($this->keyValueSeparator, reset($values), $countDataKeys - 1));
                $lastFieldValues = array_slice($values, 1);
                // Add empty missing values. The number cannot be higher.
                // TODO Use substr_count() if quicker.
                $missing = $countDataKeys - 1 - count($firstFieldsValues);
                if ($missing) {
                    $firstFieldsValues = array_merge($firstFieldsValues, array_fill(0, $missing, ''));
                }
                $values = $firstFieldsValues;
                $values[] = $lastFieldValues;
                $data = array_combine(array_keys($this->dataKeys), $values);
                // Manage sub-values.
                foreach ($arrayKeys as $arrayKey => $arraySeparator) {
                    $isLastKey = $arrayKey === $lastKey;
                    // The option "last is list" means the last key is a simple list, in any case.
                    if ($isLastKey) {
                        continue;
                    }
                    $data[$arrayKey] = $data[$arrayKey] === ''
                        ? []
                        : array_map('trim', explode($arraySeparator, $data[$arrayKey]));
                    if ($data[$arrayKey] && isset($this->dataAssociativeKeys[$arrayKey])) {
                        $asso = [];
                        foreach ($data[$arrayKey] as $k => $v) {
                            if (strpos($v, $this->dataAssociativeKeys[$arrayKey]) !== false) {
                                [$kk, $vv] = array_map('trim', explode($this->dataAssociativeKeys[$arrayKey], $v, 2));
                                $asso[$kk] = $vv;
                            } else {
                                $asso[$k] = $v;
                            }
                        }
                        $data[$arrayKey] = $asso;
                    }
                }
                $this->asKeyValue
                    ? $array[reset($data)] = $data
                    : $array[] = $data;
            }
        } else {
            // Create groups from empty lines, namely a double line break.
            $groups = array_filter(array_map('trim', explode("\n\n", $this->fixEndOfLine($string))));
            foreach ($groups as $group) {
                // No keys: a simple list.
                $data = array_map('trim', explode("\n", $group));
                $this->asKeyValue
                    ? $array[reset($data)] = $data
                    : $array[] = $data;
            }
        }
        return $array;
    }

    protected function arrayIsList(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }
        return $array === []
            || array_keys($array) === range(0, count($array) - 1);
    }
}
