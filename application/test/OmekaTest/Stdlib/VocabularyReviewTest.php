<?php
namespace OmekaTest\Stdlib;

use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use EasyRdf\Http\Response;
use Omeka\Api\Exception\ValidationException;
use Omeka\Entity\User;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\VocabularyReview;
use Omeka\Test\DbTestCase;

/**
 * Reviews of a vocabulary's changes from the comparison tests' files, which
 * RdfImporterTest's are.
 */
class VocabularyReviewTest extends DbTestCase
{
    const NAMESPACE_URI = 'http://example.org/compare#';

    /**
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $services;

    /**
     * @var VocabularyReview
     */
    protected $review;

    /**
     * @var int
     */
    protected $vocabularyId;

    /**
     * EasyRdf's HTTP client, while a test answers its requests.
     *
     * @var HttpClient|null
     */
    protected $httpClient;

    /**
     * The tokens of the reviews a test starts, ended after it, so their
     * files don't stay.
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
        $this->review = $this->services->get('Omeka\VocabularyReview');
        $this->vocabularyId = $this->api()->create('vocabularies', [
            'o:namespace_uri' => self::NAMESPACE_URI,
            'o:prefix' => 'compare',
            'o:label' => 'Compare',
            'o:class' => [
                ['o:local_name' => 'Thing', 'o:label' => 'Thing', 'o:comment' => 'A thing.'],
            ],
            'o:property' => [
                ['o:local_name' => 'name', 'o:label' => 'Name', 'o:comment' => 'A name.'],
                ['o:local_name' => 'title', 'o:label' => 'Title', 'o:comment' => 'The title.'],
            ],
        ])->getContent()->id();
    }

    public function tearDown(): void
    {
        foreach ($this->tokens as $token) {
            $this->review->cancel($token);
        }
        if ($this->httpClient) {
            Http::setDefaultHttpClient($this->httpClient);
        }
        $this->services->get('Omeka\AuthenticationService')->clearIdentity();
        parent::tearDown();
    }

    protected function api()
    {
        return $this->services->get('Omeka\ApiManager');
    }

    /**
     * Start a review of one of RdfImporterTest's files.
     *
     * @return string The review's token
     */
    protected function start($file = 'compare.ttl', array $readingOptions = ['format' => 'turtle'], array $reviewOptions = [])
    {
        $url = $this->review->start($this->vocabularyId, dirname(__DIR__) . '/Stdlib/_files/' . $file, $readingOptions, $reviewOptions);
        $this->assertMatchesRegularExpression(sprintf('#^/admin/vocabulary/%s/review\?review=[0-9a-f]{32}$#', $this->vocabularyId), $url);
        return $this->tokens[] = substr($url, strpos($url, '=') + 1);
    }

    protected function vocabulary()
    {
        $this->services->get('Omeka\EntityManager')->clear();
        return $this->api()->read('vocabularies', $this->vocabularyId)->getContent();
    }

    protected function propertyLabel($localName)
    {
        return $this->api()->search('properties', ['vocabulary_id' => $this->vocabularyId, 'local_name' => $localName])->getContent()[0]->label();
    }

    public function testStartKeepsACopyOfTheFile()
    {
        $copy = tempnam(sys_get_temp_dir(), 'test');
        copy(__DIR__ . '/_files/compare.ttl', $copy);
        $url = $this->review->start($this->vocabularyId, $copy, ['format' => 'turtle']);
        unlink($copy);
        $review = $this->review->get(substr($url, strpos($url, '=') + 1));
        $this->assertNotSame($copy, $review['file']);
        $this->assertFileEquals(__DIR__ . '/_files/compare.ttl', $review['file']);
        $this->review->cancel(substr($url, strpos($url, '=') + 1));
    }

    public function testStartingReading()
    {
        // With no stored reading, a guess, of the language and comments.
        $review = $this->review->get($this->start());
        $this->assertSame(['format' => 'turtle', 'lang' => null, 'comment_property' => RdfImporter::COMMENT_PROPERTY], $review['reading']);

        // The stored reading, where options given are empty, as an
        // unchanged form posts them.
        $this->api()->update('vocabularies', $this->vocabularyId, [
            'o:import_options' => ['lang' => 'fr', 'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY],
        ], [], ['isPartial' => true]);
        $review = $this->review->get($this->start('compare.ttl', ['format' => 'turtle', 'lang' => '', 'comment_property' => null]));
        $this->assertSame(['format' => 'turtle', 'lang' => 'fr', 'comment_property' => RdfImporter::DEFINITION_COMMENT_PROPERTY], $review['reading']);

        // An option given, over the stored one.
        $review = $this->review->get($this->start('compare.ttl', ['format' => 'turtle', 'lang' => 'en']));
        $this->assertSame('en', $review['reading']['lang']);
    }

    public function testAcceptAppliesEverythingAndEnds()
    {
        $token = $this->start('compare-moved.ttl', ['format' => 'turtle'], [
            'label' => 'Moved',
            'namespace_uri' => 'http://example.org/moved#',
        ]);
        $file = $this->review->get($token)['file'];
        $this->review->accept($token, $this->review->getReading($this->review->get($token), ['lang' => 'en']));

        $vocabulary = $this->vocabulary();
        $this->assertSame('Moved', $vocabulary->label());
        $this->assertSame('http://example.org/moved#', $vocabulary->namespaceUri());
        $this->assertSame('en', $vocabulary->importOptions()['lang']);
        // The term changes, found across the change of namespace.
        $this->assertSame('Heading', $this->propertyLabel('title'));
        // The review has ended, and its file gone.
        $this->assertNull($this->review->get($token));
        $this->assertFileDoesNotExist($file);
    }

    public function testAcceptChangesNothingWhenRefused()
    {
        // The namespace of the file's terms, which another vocabulary has
        // since taken, so the API refuses it.
        $token = $this->start('compare-moved.ttl', ['format' => 'turtle'], [
            'label' => 'Refused',
            'namespace_uri' => 'http://example.org/moved#',
        ]);
        $this->api()->create('vocabularies', [
            'o:namespace_uri' => 'http://example.org/moved#',
            'o:prefix' => 'moved',
            'o:label' => 'Moved',
        ]);
        try {
            $this->review->accept($token, $this->review->get($token)['reading']);
            $this->fail('The review was accepted');
        } catch (ValidationException $e) {
        }
        $this->assertSame('Compare', $this->vocabulary()->label());
        $this->assertSame('Title', $this->propertyLabel('title'));
        // The review goes on.
        $this->assertNotNull($this->review->get($token));
        $this->review->cancel($token);
    }

    public function testStartRefusesAFileWithoutTermsInTheNamespace()
    {
        $pattern = $this->services->get('Config')['temp_dir'] . '/' . VocabularyReview::FILE_PREFIX . '*';
        $files = glob($pattern);
        try {
            // The vocabulary's terms moved to another namespace in this file.
            $this->review->start($this->vocabularyId, __DIR__ . '/_files/compare-moved.ttl', ['format' => 'turtle']);
            $this->fail('The review started');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('http://example.org/moved#', (string) $e->getErrorStore()->getErrors()['o:namespace_uri'][0]);
        }
        $this->assertSame($files, glob($pattern));
    }

    public function testReviewWithoutAFile()
    {
        $url = $this->review->startWithoutFile($this->vocabularyId, [
            'label' => 'Moved',
            'namespace_uri' => 'http://example.org/moved#',
        ]);
        $token = $this->tokens[] = substr($url, strpos($url, '=') + 1);
        $review = $this->review->get($token);
        $this->assertNull($review['file']);
        $this->assertSame([], $review['reading']);
        // Nothing to compare: its changes are the vocabulary's own fields.
        $none = ['new' => [], 'label' => [], 'comment' => []];
        $this->assertSame(['classes' => $none, 'properties' => $none], $this->review->getDiff($review, []));
        $this->assertSame(['classes' => [], 'properties' => []], $this->review->getMissingTerms($review, []));

        $this->review->accept($token, []);
        $vocabulary = $this->vocabulary();
        $this->assertSame('Moved', $vocabulary->label());
        $this->assertSame('http://example.org/moved#', $vocabulary->namespaceUri());
        // The terms are as they were, and no reading is stored, as no file
        // was read.
        $this->assertSame('Title', $this->propertyLabel('title'));
        $this->assertNull($vocabulary->importOptions());
        $this->assertNull($this->review->get($token));
    }

    public function testCancelChangesNothingAndEnds()
    {
        $token = $this->start('compare.ttl', ['format' => 'turtle'], ['label' => 'Cancelled']);
        $file = $this->review->get($token)['file'];
        $this->review->cancel($token);
        $this->assertNull($this->review->get($token));
        $this->assertFileDoesNotExist($file);
        $this->assertSame('Compare', $this->vocabulary()->label());
        $this->assertSame('Title', $this->propertyLabel('title'));
    }

    public function testReturnUrl()
    {
        $token = $this->start('compare.ttl', ['format' => 'turtle'], ['return_url' => '/admin/vocabulary-library?page=2']);
        $this->assertSame('/admin/vocabulary-library?page=2', $this->review->getReturnUrl($this->review->get($token)));
        $this->review->cancel($token);
        $this->assertSame('/admin/vocabulary', $this->review->getReturnUrl());

        foreach (['https://example.com/admin', '//example.com/admin', '/\\example.com', 'admin/vocabulary'] as $returnUrl) {
            try {
                $this->review->start($this->vocabularyId, __DIR__ . '/_files/compare.ttl', [], ['return_url' => $returnUrl]);
                $this->fail("A return URL of $returnUrl was taken");
            } catch (\InvalidArgumentException $e) {
            }
        }
    }

    public function testOldFilesAreRemovedWhenAReviewStarts()
    {
        $tempDir = $this->services->get('Config')['temp_dir'];
        $old = tempnam($tempDir, VocabularyReview::FILE_PREFIX);
        touch($old, time() - VocabularyReview::MAX_AGE - 60);
        $recent = tempnam($tempDir, VocabularyReview::FILE_PREFIX);
        $this->review->cancel($this->start());
        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($recent);
        unlink($recent);
    }

    /**
     * Answer EasyRdf's requests with a response, in place of a server.
     *
     * @return HttpClient The client answering, which counts the requests in
     *   $requests
     */
    protected function answerRequests(Response $response)
    {
        $client = new class extends HttpClient {
            public $response;
            public $requests = 0;

            public function request($method = null)
            {
                $this->requests++;
                return $this->response;
            }
        };
        $client->response = $response;
        $this->httpClient = Http::getDefaultHttpClient();
        Http::setDefaultHttpClient($client);
        return $client;
    }

    public function testStartFromUrlFetchesOnce()
    {
        $client = $this->answerRequests(new Response(200, ['Content-Type' => 'text/turtle'], file_get_contents(__DIR__ . '/_files/compare.ttl')));
        $url = $this->review->startFromUrl($this->vocabularyId, 'http://example.org/compare.ttl', ['format' => 'turtle']);
        $token = substr($url, strpos($url, '=') + 1);
        $review = $this->review->get($token);
        $this->review->getDiff($review, $review['reading']);
        $this->review->getDiff($review, $review['reading']);
        $this->assertSame(1, $client->requests);
        $this->review->cancel($token);
    }

    public function testStartFromUrlTheServerRefuses()
    {
        $this->answerRequests(new Response(404, [], 'Not found'));
        $pattern = $this->services->get('Config')['temp_dir'] . '/' . VocabularyReview::FILE_PREFIX . '*';
        $files = glob($pattern);
        try {
            $this->review->startFromUrl($this->vocabularyId, 'http://example.org/missing.ttl');
            $this->fail('The review started');
        } catch (ValidationException $e) {
            $this->assertSame('Could not load vocabulary from URL.', $e->getMessage());
        }
        // No file is left for the review that didn't start.
        $this->assertSame($files, glob($pattern));
    }
}
