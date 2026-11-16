<?php
namespace OmekaTest\Controller\Admin;

use Laminas\Form\Element\Csrf;
use Laminas\Stdlib\Parameters;
use Omeka\Entity\User;
use Omeka\Form\ResourceTemplateImportForm;
use Omeka\Test\AbstractHttpControllerTestCase;

/**
 * Importing a resource template, signed in as the test database's admin,
 * whose vocabulary the template names by its namespace URI.
 *
 * What a request writes is committed, so each test deletes the vocabulary it
 * makes.
 */
class ResourceTemplateControllerTest extends AbstractHttpControllerTestCase
{
    /**
     * @var int
     */
    protected $vocabularyId;

    /**
     * @var string|null
     */
    protected $file;

    public function setUp(): void
    {
        parent::setUp();
        $this->signIn();
        $this->vocabularyId = $this->getApplicationServiceLocator()->get('Omeka\ApiManager')->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/template#',
            'o:prefix' => 'template',
            'o:label' => 'Template',
            'o:class' => [['o:local_name' => 'Thing', 'o:label' => 'Thing']],
            'o:property' => [['o:local_name' => 'name', 'o:label' => 'Name']],
        ])->getContent()->id();
    }

    public function tearDown(): void
    {
        if ($this->file) {
            unlink($this->file);
        }
        $this->reset(true);
        $this->signIn();
        $this->getApplicationServiceLocator()->get('Omeka\ApiManager')->delete('vocabularies', $this->vocabularyId);
        parent::tearDown();
    }

    protected function signIn()
    {
        $services = $this->getApplicationServiceLocator();
        $admin = $services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $services->get('Omeka\AuthenticationService')->getStorage()->write($admin);
    }

    /**
     * Post a template whose class and property are the tests' vocabulary's,
     * named by a namespace URI, and get the review page as a browser reads
     * it.
     *
     * @param string $namespaceUri
     * @return string
     */
    protected function import($namespaceUri)
    {
        $term = ['vocabulary_namespace_uri' => $namespaceUri, 'vocabulary_label' => 'Template'];
        $this->file = tempnam(sys_get_temp_dir(), 'template');
        file_put_contents($this->file, json_encode([
            'o:label' => 'Imported',
            'o:resource_class' => $term + ['local_name' => 'Thing', 'label' => 'Thing'],
            'o:resource_template_property' => [$term + [
                'local_name' => 'name',
                'label' => 'Name',
                'o:alternate_label' => null,
                'o:alternate_comment' => null,
                'o:is_required' => false,
                'o:is_private' => false,
                'data_types' => [['name' => 'literal', 'label' => 'Text']],
                // As older exports have it, which the review page shows for
                // a property of an unknown vocabulary.
                'data_type_name' => 'literal',
                'data_type_label' => 'Text',
            ]],
        ]));
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get(ResourceTemplateImportForm::class);
        $csrf = [];
        foreach ($form->getElements() as $element) {
            if ($element instanceof Csrf) {
                $csrf[$element->getName()] = $element->getValue();
            }
        }
        $this->reset(true);
        $this->signIn();
        $this->getRequest()->setFiles(new Parameters(['file' => [
            'name' => 'template.json',
            'type' => 'application/json',
            'tmp_name' => $this->file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($this->file),
        ]]));
        $this->dispatch('/admin/resource-template/import', 'POST', $csrf);
        $this->assertResponseStatusCode(200);
        $this->assertTemplateName('omeka/admin/resource-template/review-import');
        return html_entity_decode($this->getResponse()->getContent(), ENT_QUOTES | ENT_HTML5);
    }

    public function namespaceUris()
    {
        return [
            'the same' => ['http://example.org/template#'],
            'another form' => ['https://example.org/template'],
        ];
    }

    /**
     * @dataProvider namespaceUris
     */
    public function testImportFindsTheVocabulary($namespaceUri)
    {
        $page = $this->import($namespaceUri);
        $this->assertStringNotContainsString('unknown vocabulary', $page);
        $this->assertStringContainsString('"vocabulary_prefix":"template"', $page);
    }

    public function testImportOfAnotherVocabulary()
    {
        $page = $this->import('http://example.org/other#');
        $this->assertStringContainsString('unknown vocabulary', $page);
    }
}
