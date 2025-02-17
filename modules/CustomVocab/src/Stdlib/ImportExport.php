<?php
namespace CustomVocab\Stdlib;

use Omeka\Api\Exception\NotFoundException;

class ImportExport
{
    protected $api;

    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * Get the export array for a custom vocab.
     *
     * @param int $customVocabId The custom vocab ID
     * @return array|false Returns false if cannot export
     */
    public function getExport(int $customVocabId)
    {
        try {
            $vocab = $this->api->read('custom_vocabs', $customVocabId)->getContent();
        } catch (NotFoundException $e) {
            // Invalid custom vocab
            return false;
        }
        $export = [
            'o:label' => $vocab->label(),
            'o:lang' => $vocab->lang(),
        ];
        switch ($vocab->type()) {
            case 'literal':
                $export['o:terms'] = $vocab->terms();
                break;
            case 'uri':
                $export['o:uris'] = $vocab->uris();
                break;
            case 'resource':
            default:
                // Cannot export item lists
                return false;
        }
        return $export;
    }

    /**
     * Is the import valid?
     *
     * @param array $import
     * @return bool Returns true if valid, false if invalid
     */
    public function isValidImport($import)
    {
        if (!is_array($import)) {
            return false;
        }
        if (!array_key_exists('o:label', $import)) {
            return false;
        }
        if (!array_key_exists('o:lang', $import)) {
            return false;
        }
        if (!(array_key_exists('o:terms', $import) || array_key_exists('o:uris', $import))) {
            return false;
        }
        if (array_key_exists('o:terms', $import)) {
            if (!is_array($import['o:terms'])) {
                return false;
            }
            foreach ($import['o:terms'] as $value) {
                if (!is_string($value)) {
                    return false;
                }
            }
        }
        if (array_key_exists('o:uris', $import)) {
            if (!is_array($import['o:uris'])) {
                return false;
            }
            foreach ($import['o:uris'] as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
                if (!is_string($value)) {
                    return false;
                }
            }
        }
        return true;
    }
}
