<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class Repeatable extends Element implements InputProviderInterface
{
    protected $value = [];

    public function getFields(): array
    {
        return $this->options['fields'] ?? [];
    }

    public function isSortable(): bool
    {
        return (bool) ($this->options['sortable'] ?? false);
    }

    public function getMinRows(): int
    {
        return (int) ($this->options['min_rows'] ?? 0);
    }

    public function getMaxRows(): ?int
    {
        return isset($this->options['max_rows']) ? (int) $this->options['max_rows'] : null;
    }

    private function decodeJson(string $json): array
    {
        return json_decode($json, true) ?? [];
    }

    public function setValue($value)
    {
        if (is_array($value)) {
            $this->value = $value;
        } elseif (is_string($value) && $value !== '') {
            $this->value = $this->decodeJson($value);
        } else {
            $this->value = [];
        }
        return $this;
    }

    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => (bool) ($this->options['required'] ?? false),
            'allow_empty' => true,
            'filters' => [
                [
                    'name' => \Laminas\Filter\Callback::class,
                    'options' => [
                        'callback' => function ($value) {
                            $rows = is_string($value) ? $this->decodeJson($value) : [];
                            return array_values(array_filter($rows, function ($row) {
                                foreach ($row as $fieldValue) {
                                    if ($fieldValue !== '') {
                                        return true;
                                    }
                                }
                                return false;
                            }));
                        },
                    ],
                ],
            ],
        ];
    }
}
