<?php
namespace OmekaTest\Form;

use Omeka\Form\VocabularyFileForm;
use Omeka\Stdlib\RdfImporter;
use Omeka\Test\TestCase;

class VocabularyFileFormTest extends TestCase
{
    public function testTheFormatsLabeledAreTheImportersOwn()
    {
        $this->assertSame(RdfImporter::FORMATS, array_keys(VocabularyFileForm::FORMAT_LABELS));
    }
}
