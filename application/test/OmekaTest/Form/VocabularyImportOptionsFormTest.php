<?php
namespace OmekaTest\Form;

use Omeka\Form\VocabularyImportOptionsForm;
use Omeka\Stdlib\RdfImporter;
use Omeka\Test\TestCase;

class VocabularyImportOptionsFormTest extends TestCase
{
    public function testLabelsFromGivesTheLabelProperty()
    {
        // The default is no label property of its own.
        $this->assertSame(['label_property' => null], VocabularyImportOptionsForm::getChosenImportOptions(['labels' => 'label', 'label_property' => 'ignored']));
        $this->assertSame(['label_property' => 'skos:altLabel'], VocabularyImportOptionsForm::getChosenImportOptions(['labels' => 'other', 'label_property' => ' skos:altLabel ']));
        // Another property with none entered is no choice, as for comments.
        $this->assertSame([], VocabularyImportOptionsForm::getChosenImportOptions(['labels' => 'other', 'label_property' => ' ']));
        $this->assertSame([], VocabularyImportOptionsForm::getChosenImportOptions([]));
    }

    public function testLabelsFromStartsFromTheLabelProperty()
    {
        $this->assertSame(['label', ''], $this->labelsFrom([]));
        $this->assertSame(['label', ''], $this->labelsFrom(['label_property' => RdfImporter::LABEL_PROPERTY]));
        $this->assertSame(['other', 'skos:altLabel'], $this->labelsFrom(['label_property' => 'skos:altLabel']));
    }

    /**
     * Set a form's choices to import options, and get what its Labels from
     * and Label property fields then show.
     *
     * @param array $importOptions
     * @return array
     */
    protected function labelsFrom(array $importOptions)
    {
        $form = new VocabularyImportOptionsForm;
        $form->init();
        $form->setChoices($importOptions);
        return [$form->get('labels')->getValue(), (string) $form->get('label_property')->getValue()];
    }
}
