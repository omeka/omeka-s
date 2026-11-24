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
            $this->vocabularyImport->cancel($token);
        }
        $this->services->get('Omeka\AuthenticationService')->clearIdentity();
        parent::tearDown();
    }

    /**
     * Start an import of one of RdfImporterTest's files.
     *
     * @return array The import, as VocabularyImport::get() gives it
     */
    protected function start($file = 'compare.ttl', array $importOptions = ['format' => 'turtle'], array $vocabulary = [])
    {
        $url = $this->vocabularyImport->start(__DIR__ . '/_files/' . $file, $vocabulary, $importOptions);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->tokens[] = $query['token'];
        return $this->vocabularyImport->get($query['token']);
    }

    /**
     * Import the file of an import as the tests' vocabulary.
     *
     * @param array $import
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function import(array $import)
    {
        return $this->vocabularyImport->import($import['token'], [
            'o:namespace_uri' => self::NAMESPACE_URI,
            'o:prefix' => 'compare',
            'o:label' => 'Compare',
            'o:comment' => '',
        ], ['format' => 'turtle']);
    }

    public function testGetGivesTheToken()
    {
        $import = $this->start();
        $this->assertSame(end($this->tokens), $import['token']);
    }

    public function testStartKeepsTheFormat()
    {
        $this->assertSame('turtle', $this->start()['import_options']['format']);
    }

    public function testGetFileDetailsReadsTheFile()
    {
        $details = $this->vocabularyImport->getFileDetails($this->start(), 'en_US');
        $this->assertSame(self::NAMESPACE_URI, $details['namespace_uri']);
    }

    public function testStartingFieldsAreTheFilesWithTheHandedOnesOver()
    {
        $import = $this->start('compare.ttl', ['format' => 'turtle'], ['o:label' => 'Handed', 'o:prefix' => 'handed']);
        $fileDetails = ['label' => 'From the file', 'comment' => 'Its comment', 'namespace_uri' => self::NAMESPACE_URI, 'prefix' => 'compare'];
        $this->assertSame([
            'o:label' => 'Handed',
            'o:comment' => 'Its comment',
            'o:namespace_uri' => self::NAMESPACE_URI,
            'o:prefix' => 'handed',
        ], $this->vocabularyImport->getStartingFields($import, $fileDetails));
    }

    public function testStartingChoicesAreTheFilesLanguageThenHowTheVocabularyWasReadThenTheHandedOnes()
    {
        $vocabulary = $this->import($this->start());
        $fileDetails = ['lang' => 'en'];
        // The file's language nearest the installation's.
        $this->assertSame(['lang' => 'en', 'format' => 'turtle'], $this->vocabularyImport->getStartingChoices($this->start(), $fileDetails, null));
        $import = $this->start('compare.ttl', ['format' => 'turtle', 'lang' => 'fr']);
        $this->assertSame(['lang' => 'fr', 'format' => 'turtle'], $this->vocabularyImport->getStartingChoices($import, $fileDetails, null));
        // Read as turtle alone, so the other choices start on their defaults.
        $this->assertSame([
            'lang' => 'fr',
            'label_property' => null,
            'comment_property' => null,
            'format' => 'turtle',
        ], $this->vocabularyImport->getStartingChoices($import, $fileDetails, $vocabulary));
    }

    public function testGetImportOptionsGivesEachChoice()
    {
        $import = $this->start();
        $this->assertSame([
            'format' => 'turtle',
            'lang' => 'fr',
            'label_property' => null,
            'comment_property' => null,
        ], $this->vocabularyImport->getImportOptions($import, ['lang' => 'fr', 'format' => 'n3']));
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
        $this->assertNull($this->vocabularyImport->get($import['token']));
    }

    public function testImportRefusesAnEndedImport()
    {
        $import = $this->start();
        $this->vocabularyImport->cancel($import['token']);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This import has ended.');
        $this->import($import);
    }

    public function testCancelEndsTheImport()
    {
        $import = $this->start();
        $this->vocabularyImport->cancel($import['token']);
        $this->assertNull($this->vocabularyImport->get($import['token']));
        $this->assertFileDoesNotExist($import['files']['source']);
    }

    public function testARefusedImportGoesOn()
    {
        $import = $this->start();
        try {
            $this->vocabularyImport->import($import['token'], [
                'o:namespace_uri' => self::NAMESPACE_URI,
                'o:prefix' => 'not a prefix',
                'o:label' => 'Compare',
                'o:comment' => '',
            ], ['format' => 'turtle']);
            $this->fail('The import was accepted');
        } catch (ValidationException $e) {
        }
        $this->assertNotNull($this->vocabularyImport->get($import['token']));
    }

    public function testFindVocabularyUnderAnyForm()
    {
        $vocabulary = $this->import($this->start());
        $this->assertSame($vocabulary->id(), $this->vocabularyImport->findVocabulary('https://example.org/compare/')->id());
        $this->assertNull($this->vocabularyImport->findVocabulary('http://example.org/other#'));
        $this->assertNull($this->vocabularyImport->findVocabulary(''));
    }
}
