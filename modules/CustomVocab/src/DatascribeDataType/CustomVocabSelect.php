<?php
namespace CustomVocab\DatascribeDataType;

use Datascribe\DatascribeDataType\DataTypeInterface;
use Datascribe\Form\Element as DatascribeElement;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Manager;
use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Validator\ValidatorChain;

/**
 * The Custom Vocab data type for the DataScribe module.
 */
class CustomVocabSelect implements DataTypeInterface
{
    /**
     * @var Manager The API manager
     */
    protected $api;

    /**
     * @var array Cache of select value options, keyed by custom vocab ID
     */
    protected $valueOptions = [];

    /**
     * @param Manager $api
     */
    public function __construct(Manager $api)
    {
        $this->api = $api;
    }

    public function getLabel() : string
    {
        return 'Custom vocab select'; // @translate
    }

    public function addFieldElements(Fieldset $fieldset, array $fieldData) : void
    {
        $element = new Element\Text('label');
        $element->setLabel('Selection label'); // @translate
        $element->setValue($fieldData['label'] ?? null);
        $fieldset->add($element);

        $valueOptions = [];
        $customVocabs = $this->api->search('custom_vocabs')->getContent();
        foreach ($customVocabs as $customVocab) {
            if ($customVocab->terms()) {
                // Only allow custom vocabs that are a list of terms.
                $valueOptions[$customVocab->id()] = $customVocab->label();
            }
        }
        $element = new Element\Select('custom_vocab_id');
        $element->setLabel('Custom vocab'); // @translate
        $element->setValue($fieldData['custom_vocab_id'] ?? null);
        $element->setValueOptions($valueOptions);
        $element->setEmptyOption('Select one:'); // @translate
        $element->setAttribute('required', true);
        $fieldset->add($element);
    }

    public function getFieldDataFromUserData(array $userData) : array
    {
        $fieldData = [];
        $fieldData['label'] =
            (isset($userData['label']) && preg_match('/^.+$/', $userData['label']))
            ? $userData['label'] : null;
        $fieldData['custom_vocab_id'] =
            (isset($userData['custom_vocab_id']) && is_numeric($userData['custom_vocab_id']))
            ? $userData['custom_vocab_id'] : null;
        return $fieldData;
    }

    public function fieldDataIsValid(array $fieldData) : bool
    {
        return true;
    }

    public function addValueElements(Fieldset $fieldset, array $fieldData, ?string $valueText) : void
    {
        $fieldData['options'] = $this->getValueOptions($fieldData['custom_vocab_id']);
        $element = new DatascribeElement\Select('value', [
            'datascribe_field_data' => $fieldData,
        ]);
        $element->setLabel($fieldData['label'] ?? 'Select'); // @translate
        $element->setAttribute('class', 'chosen-select');
        $element->setAttribute('data-placeholder', '[No selection]'); // @translate
        $element->setValue($valueText ?? '');
        $fieldset->add($element);
    }

    public function getValueTextFromUserData(array $userData) : ?string
    {
        $text = null;
        if (isset($userData['value']) && is_string($userData['value']) && ('' !== $userData['value'])) {
            $text = $userData['value'];
        }
        return $text;
    }

    public function valueTextIsValid(array $fieldData, ?string $valueText) : bool
    {
        $fieldData['options'] = $this->getValueOptions($fieldData['custom_vocab_id']);
        $element = new DatascribeElement\Select('value', [
            'datascribe_field_data' => $fieldData,
        ]);
        $validatorChain = new ValidatorChain;
        foreach ($element->getValidators() as $validator) {
            $validatorChain->attach($validator);
        }
        return isset($valueText) ? $validatorChain->isValid($valueText) : false;
    }

    /**
     * Get value options for a custom vocab select.
     *
     * @param int $customVocabId The custom vocab ID
     */
    protected function getValueOptions($customVocabId)
    {
        if (isset($this->valueOptions[$customVocabId])) {
            // Return the cached value options to avoid duplicate API reads.
            return $this->valueOptions[$customVocabId];
        }
        try {
            $customVocab = $this->api->read('custom_vocabs', $customVocabId)->getContent();
            $valueOptions = $customVocab->listValues(['append_id_to_title' => true]);
        } catch (NotFoundException $e) {
            $valueOptions = [];
        }
        // Cache and return the value options.
        $this->valueOptions[$customVocabId] = $valueOptions;
        return $valueOptions;
    }
}
