<?php
namespace OmekaTest\Stdlib;

use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Stdlib\VocabularyImport;
use Omeka\Test\DbTestCase;

/**
 * An import's steps after start(): what its file says, the vocabulary Omeka
 * has, the classes and properties it would add, and the import itself.
 */
class VocabularyImportTest extends DbTestCase
{
    const NAMESPACE_URI = 'http://example.org/compare#';

    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $services;

    /**
     * @var VocabularyImport
     */
    protected $vocabularyImport;

    /**
     * The tokens of the imports a test starts, ended after it, so their files
     * don't stay.
     *
     * @var string[]
     */
    protected $tokens = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->services = self::getApplication()->getServiceManager();
        $admin = $this->services->get('Omeka\EntityManager')->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $this->services->get('Omeka\AuthenticationService')->getStorage()->write($admin);
        $this->vocabularyImport = $this->services->get('Omeka\VocabularyImport');
    }

    public function tearDown(): void
    {
        foreach ($this->tokens as $token) {
            $this->vocabularyImport->end($token);
        }
        $this->services->get('Omeka\AuthenticationService')->clearIdentity();
        parent::tearDown();
    }

    /**
     * Start an import of one of RdfImporterTest's files.
     *
     * @return array The import, as VocabularyImport::get() gives it
     */
    protected function start($file = 'compare.ttl')
    {
        $url = $this->vocabularyImport->start(__DIR__ . '/_files/' . $file, [], ['format' => 'turtle']);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->tokens[] = $query['file_token'];
        return $this->vocabularyImport->get($query['file_token']);
    }

    /**
     * Import the file of an import as the tests' vocabulary.
     *
     * @param array $import
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function import(array $import)
    {
        return $this->vocabularyImport->import($import, [
            'o:namespace_uri' => self::NAMESPACE_URI,
            'o:prefix' => 'compare',
            'o:label' => 'Compare',
            'o:comment' => '',
        ], ['format' => 'turtle']);
    }

    public function testGetGivesTheFileToken()
    {
        $import = $this->start();
        $this->assertSame(end($this->tokens), $import['file_token']);
    }

    public function testGetDetailsReadsTheFile()
    {
        $details = $this->vocabularyImport->getDetails($this->start(), 'turtle', 'en_US');
        $this->assertSame(self::NAMESPACE_URI, $details['namespace_uri']);
    }

    public function testGetMembersRefusesANamespaceWithoutTerms()
    {
        $import = $this->start();
        $members = $this->vocabularyImport->getMembers($import, self::NAMESPACE_URI, ['format' => 'turtle']);
        $this->assertArrayHasKey('Thing', $members['classes']);
        $this->expectException(ValidationException::class);
        $this->vocabularyImport->getMembers($import, 'http://example.org/other#', ['format' => 'turtle']);
    }

    public function testImportEndsTheImport()
    {
        $import = $this->start();
        $vocabulary = $this->import($import);
        $this->assertSame(self::NAMESPACE_URI, $vocabulary->namespaceUri());
        $this->assertNull($this->vocabularyImport->get($import['file_token']));
    }

    public function testARefusedImportGoesOn()
    {
        $import = $this->start();
        try {
            $this->vocabularyImport->import($import, [
                'o:namespace_uri' => self::NAMESPACE_URI,
                'o:prefix' => 'not a prefix',
                'o:label' => 'Compare',
                'o:comment' => '',
            ], ['format' => 'turtle']);
            $this->fail('The import was accepted');
        } catch (ValidationException $e) {
        }
        $this->assertNotNull($this->vocabularyImport->get($import['file_token']));
    }

    public function testFindVocabularyUnderAnyForm()
    {
        $vocabulary = $this->import($this->start());
        $this->assertSame($vocabulary->id(), $this->vocabularyImport->findVocabulary('https://example.org/compare/')->id());
        $this->assertNull($this->vocabularyImport->findVocabulary('http://example.org/other#'));
        $this->assertNull($this->vocabularyImport->findVocabulary(''));
    }
}
