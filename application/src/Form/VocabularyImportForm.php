<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'vocabulary-info',
            'type' => 'Omeka\Form\Element\VocabularyInfo',
        ]);

        $this->add([
            'name' => 'vocabulary-namespace',
            'type' => 'Omeka\Form\Element\VocabularyNamespace',
        ]);

        $this->add([
            'name' => 'vocabulary-file',
            'type' => 'Omeka\Form\Element\VocabularyFile',
        ]);

        $this->add([
            'name' => 'vocabulary-advanced',
            'type' => 'Omeka\Form\Element\VocabularyAdvanced',
        ]);
    }
}
