<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Form\Element\ArrayTextarea;

class GroupTextarea extends ArrayTextarea
{
    /**
     * The default group name must contain a "%s" to set the group number.
     *
     *@var string
     */
    protected $defaultGroupName = '';

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        if (array_key_exists('default_group_name', $this->options)) {
            $this->setDefaultGroupName($this->options['default_group_name']);
        }
        return $this;
    }

    public function arrayToString($array): string
    {
        if (is_string($array)) {
            return $array;
        } elseif (!$array) {
            return '';
        }

        $string = '';
        if ($this->asKeyValue) {
            foreach ($array ?? [] as $group => $values) {
                $string .= '# ' . $group . "\n";
                foreach ($values ?? [] as $key => $value) {
                    $string .= strlen($value) ? "$key $this->keyValueSeparator $value\n" : $key . "\n";
                }
                $string .= "\n";
            }
        } else {
            foreach ($array ?? [] as $group => $values) {
                $string .= '# ' . $group . "\n" . implode("\n", $values) . "\n\n";
            }
        }

        return trim($string);
    }

    public function stringToArray($string)
    {
        if (is_array($string)) {
            return $string;
        } elseif ($string === '' || is_null($string)) {
            return [];
        }

        // Clean the text area from end of lines.
        // Fixes Windows and Apple issues for copy/paste.
        $string = str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], (string) $string);
        $array = array_filter(array_map('trim', explode("\n", $string)), 'strlen');

        $groupsArray = [];
        $id = 0;
        foreach ($array as $string) {
            $cleanString = preg_replace('/\s+/', ' ', $string);
            if (mb_substr($cleanString, 0, 1) === '#') {
                ++$id;
                $groupName = trim(mb_substr($cleanString, 1));
                $groupsArray[$groupName] = [];
                continue;
            } elseif ($id === 0) {
                ++$id;
                // Set a default group name when missing.
                $groupName = strpos($this->defaultGroupName, '%s') === false
                    ? (string) $id
                    : sprintf($this->defaultGroupName, (string) $id);
            }
            if ($this->asKeyValue) {
                if (strpos($cleanString, $this->keyValueSeparator) === false) {
                    $key = trim($cleanString);
                    $value = '';
                } else {
                    [$key, $value] = array_map('trim', explode($this->keyValueSeparator, $cleanString, 2));
                }
                $groupsArray[$groupName][$key] = $value;
            } else {
                $groupsArray[$groupName][] = $cleanString;
            }
        }
        return $groupsArray;
    }

    /**
     * Set the option to indicate the default group name. It must contains "%s".
     *
     * @param string $defaultGroupName
     */
    public function setDefaultGroupName($defaultGroupName): self
    {
        $this->defaultGroupName = (string) $defaultGroupName;
        return $this;
    }

    /**
     * Set the option to indicate the default group name.
     */
    public function getDefaultGroupName(): string
    {
        return $this->defaultGroupName;
    }
}
