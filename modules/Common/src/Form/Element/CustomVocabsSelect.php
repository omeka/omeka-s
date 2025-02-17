<?php declare(strict_types=1);

namespace Common\Form\Element;

use Common\Stdlib\EasyMeta;
use Laminas\Form\Element\Select;

class CustomVocabsSelect extends Select
{
    use TraitOptionalElement;
    use TraitPrependValuesOptions;

    protected $attributes = [
        'type' => 'select',
        'multiple' => false,
    ];

    /**
     * @var \Common\Stdlib\EasyMeta
     */
    protected $easyMeta;

    public function getValueOptions(): array
    {
        $customVocabTypes = $this->easyMeta->dataTypeMainCustomVocabs();
        $customVocabType = $this->getOption('custom_vocab_type');
        $valueOptions = $customVocabType
            ? $this->easyMeta->dataTypeLabels(array_keys($customVocabTypes, $customVocabType))
            : $this->easyMeta->dataTypeLabels(array_keys($customVocabTypes));

        return $this->prependValuesOptions($valueOptions);
    }

    public function setEasyMeta(EasyMeta $easyMeta): self
    {
        $this->easyMeta = $easyMeta;
        return $this;
    }
}
