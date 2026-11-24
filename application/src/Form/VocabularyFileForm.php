<?php
namespace Omeka\Form;

use Laminas\Form\Form;

/**
 * The file to import a vocabulary from, or to update one from: an upload or a
 * URL, and the format to read it in.
 */
class VocabularyFileForm extends Form
{
    /**
     * The labels of RdfImporter::FORMATS, as the File format field offers
     * them.
     */
    const FORMAT_LABELS = [
        'guess' => '[Autodetect]', // @translate
        'jsonld' => 'JSON-LD (.jsonld)', // @translate
        'ntriples' => 'N-Triples (.nt)', // @translate
        'rdfxml' => 'RDF/XML (.rdf)', // @translate
        // One option: EasyRdf reads Notation3 as Turtle, and two options of
        // one value would both show as chosen.
        'turtle' => 'Turtle or Notation3 (.ttl, .n3)', // @translate
    ];

    /**
     * - vocabulary: (optional) The vocabulary to update, whose file's last
     *   format the File format field starts on
     */
    protected $options = [
        'vocabulary' => null,
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $vocabulary = $this->getOption('vocabulary');
        $this->add([
            'name' => 'import_type',
            'type' => 'radio',
            'options' => [
                'label' => 'Import type', // @translate
                'value_options' => [
                    'upload' => 'Upload', // @translate
                    'url' => 'URL', // @translate
                ],
            ],
            'attributes' => [
                'value' => 'upload',
                'class' => 'import-type-select',
            ],
        ]);
        $this->add([
            'name' => 'file',
            'type' => 'file',
            'options' => [
                'label' => 'File upload', // @translate
                'info' => 'Choose a RDF vocabulary file.', // @translate
            ],
            'attributes' => [
                'id' => 'file-upload',
            ],
        ]);
        $this->add([
            'name' => 'url',
            'type' => 'url',
            'options' => [
                'label' => 'File URL', // @translate
                'info' => 'Enter a URL to a RDF vocabulary file.', // @translate
            ],
            'attributes' => [
                'id' => 'file-url',
            ],
        ]);
        $this->add([
            'name' => 'format',
            'type' => 'Select',
            'options' => [
                'label' => 'File format', // @translate
                'value_options' => self::FORMAT_LABELS,
            ],
            'attributes' => [
                'id' => 'format',
                'class' => 'chosen-select',
                'value' => $vocabulary ? ($vocabulary->importOptions()['format'] ?? null) : null,
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'file',
            'type' => \Laminas\InputFilter\FileInput::class,
            'required' => false,
            'validators' => [
                [
                    'name' => \Laminas\Validator\File\UploadFile::class,
                    'options' => [
                        'messages' => [
                            // In place of the validator's own, which names
                            // php.ini's upload_max_filesize.
                            \Laminas\Validator\File\UploadFile::INI_SIZE => 'The file is larger than this server accepts for uploads. Choose URL and enter the file\'s address instead.', // @translate
                        ],
                    ],
                ],
            ],
        ]);
        $inputFilter->add([
            'name' => 'url',
            'required' => false,
        ]);
    }

    /**
     * Get the file the fields choose, once the form is valid.
     *
     * @return array|null file (an upload's path) or url, and format; null
     *   where neither was given
     */
    public function getFileSource()
    {
        $data = $this->getData();
        // The form has refused a failed upload, so an empty temp name means
        // no file was chosen.
        $file = $data['file'] ?? [];
        $url = trim((string) ($data['url'] ?? ''));
        if ('upload' === $data['import_type'] && !empty($file['tmp_name'])) {
            return ['file' => $file['tmp_name'], 'format' => $data['format']];
        }
        if ('url' === $data['import_type'] && '' !== $url) {
            return ['url' => $url, 'format' => $data['format']];
        }
        return null;
    }
}
