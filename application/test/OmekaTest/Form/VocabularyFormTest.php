<?php
namespace OmekaTest\Form;

use Omeka\Form\VocabularyForm;
use Omeka\Stdlib\RdfImporter;
use Omeka\Test\TestCase;

class VocabularyFormTest extends TestCase
{
    public function testLabelsFromGivesTheLabelProperty()
    {
        // The default is no label property of its own.
        $this->assertSame(['label_property' => null], VocabularyForm::getChosenImportOptions(['labels' => 'label', 'label_property' => 'ignored']));
        $this->assertSame(['label_property' => 'skos:altLabel'], VocabularyForm::getChosenImportOptions(['labels' => 'other', 'label_property' => ' skos:altLabel ']));
        // Another property with none entered is no choice, as for comments.
        $this->assertSame([], VocabularyForm::getChosenImportOptions(['labels' => 'other', 'label_property' => ' ']));
        $this->assertSame([], VocabularyForm::getChosenImportOptions([]));
    }

    public function testLabelsFromStartsFromTheLabelProperty()
    {
        $values = VocabularyForm::getChoiceValues([]);
        $this->assertSame(['label', ''], [$values['labels'], $values['label_property']]);
        $values = VocabularyForm::getChoiceValues(['label_property' => RdfImporter::LABEL_PROPERTY]);
        $this->assertSame(['label', ''], [$values['labels'], $values['label_property']]);
        $values = VocabularyForm::getChoiceValues(['label_property' => 'skos:altLabel']);
        $this->assertSame(['other', 'skos:altLabel'], [$values['labels'], $values['label_property']]);
    }
}
