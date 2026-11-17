<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use Laminas\Router\RouteStackInterface;
use Laminas\Session\Container;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Permissions\Exception\PermissionDeniedException;

/**
 * Reviews of the changes a file would make to a vocabulary, kept on the
 * server until they're accepted or cancelled.
 *
 * A review holds a copy of the file in Omeka's temporary directory, how the
 * file is read, and any pending change to the vocabulary's namespace URI.
 * It's kept in the session under a token the review page's URL carries, so
 * the browser never holds the changes, and every look at them reads the same
 * file.
 */
class VocabularyReview
{
    /**
     * The start of the names of reviews' files in the temporary directory.
     */
    const FILE_PREFIX = 'omeka-vocabulary-review-';

    /**
     * How long a review's file is kept, in seconds, after which it's removed
     * the next time a review starts.
     */
    const MAX_AGE = 86400;

    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var ApiManager
     */
    protected $apiManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var RouteStackInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var Container
     */
    protected $session;

    /**
     * @param RdfImporter $rdfImporter
     * @param ApiManager $apiManager
     * @param EntityManager $entityManager
     * @param RouteStackInterface $router
     * @param string $tempDir
     */
    public function __construct(RdfImporter $rdfImporter, ApiManager $apiManager,
        EntityManager $entityManager, RouteStackInterface $router, $tempDir
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->apiManager = $apiManager;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->tempDir = $tempDir;
    }

    /**
     * Start a review of the changes a file would make to a vocabulary.
     *
     * @param int $vocabularyId
     * @param string $filePath The file, which is copied, so it can go
     * @param array $readingOptions How to read the file, as the importer's
     *   options: format, lang, label_property, comment_property. Those not
     *   given, or empty, are the vocabulary's stored reading, else a guess at
     *   how it was read.
     * @param array $reviewOptions
     *   - namespace_uri: (optional) A pending namespace URI for the
     *     vocabulary, the one the file's terms use, accepted with the changes
     *   - return_url: (optional) Where Accept and Cancel go: a local path,
     *     such as "/admin/vocabulary". By default, the vocabulary's page.
     *   - source_label: (optional) A name for the file's side of the
     *     changes, such as where it came from. By default, "the file".
     * @return string The review page's URL
     * @throws ValidationException When the file can't be read, has no
     *   terms in the namespace URI (with the one its terms use, where
     *   there is one), or has terms too long to store
     */
    public function start($vocabularyId, $filePath, array $readingOptions = [], array $reviewOptions = [])
    {
        if (!is_readable($filePath)) {
            throw new ValidationException('Could not read vocabulary file.'); // @translate
        }
        return $this->begin($vocabularyId, function ($path) use ($filePath) {
            return copy($filePath, $path);
        }, $readingOptions, $reviewOptions);
    }

    /**
     * Start a review of the changes the file at a URL would make to a
     * vocabulary. The file is fetched once, as EasyRdf would fetch it.
     *
     * @see self::start()
     * @param int $vocabularyId
     * @param string $url
     * @param array $readingOptions
     * @param array $reviewOptions
     * @return string The review page's URL
     */
    public function startFromUrl($vocabularyId, $url, array $readingOptions = [], array $reviewOptions = [])
    {
        return $this->begin($vocabularyId, function ($path) use ($url) {
            return false !== file_put_contents($path, $this->fetch($url));
        }, $readingOptions, $reviewOptions);
    }

    /**
     * Get a review.
     *
     * @param string $token
     * @return array|null The review, or null if there's none by the token, or
     *   its file has gone: vocabulary_id, file, reading, namespace_uri,
     *   return_url, source_label
     */
    public function get($token)
    {
        $review = $this->getSession()->offsetGet((string) $token);
        if (!is_array($review) || !is_readable($review['file'])) {
            return null;
        }
        return $review;
    }

    /**
     * Get the reading options of a review, with any the admin chose in place
     * of its own.
     *
     * @param array $review
     * @param array $choices Reading options, such as lang and
     *   comment_property; others are ignored
     * @return array
     */
    public function getReading(array $review, array $choices = [])
    {
        return array_intersect_key($choices, array_flip(RdfImporter::READING_OPTIONS)) + $review['reading'];
    }

    /**
     * Get the namespace URI a review reads its file with: the pending one,
     * else the vocabulary's.
     *
     * @param array $review
     * @return string
     */
    public function getNamespaceUri(array $review)
    {
        return $review['namespace_uri']
            ?? $this->apiManager->read('vocabularies', $review['vocabulary_id'])->getContent()->namespaceUri();
    }

    /**
     * Get the changes a review's file would make, read as given.
     *
     * @see RdfImporter::getDiff()
     * @param array $review
     * @param array $reading
     * @return array
     */
    public function getDiff(array $review, array $reading)
    {
        return $this->rdfImporter->getDiff('file', $this->getNamespaceUri($review), [
            'file' => $review['file'],
            'vocabulary_id' => $review['vocabulary_id'],
        ] + $reading);
    }

    /**
     * Get the classes and properties the vocabulary has that a review's file
     * doesn't, read as given.
     *
     * @see RdfImporter::getMissingTerms()
     * @param array $review
     * @param array $reading
     * @return array
     */
    public function getMissingTerms(array $review, array $reading)
    {
        return $this->rdfImporter->getMissingTerms($review['vocabulary_id'], 'file', [
            'file' => $review['file'],
            'namespace_uri' => $this->getNamespaceUri($review),
        ] + $reading);
    }

    /**
     * Get the languages of a review's file.
     *
     * @see RdfImporter::getLanguages()
     * @param array $review
     * @param array $reading
     * @return string[]
     */
    public function getLanguages(array $review, array $reading)
    {
        return $this->rdfImporter->getLanguages('file', $this->getNamespaceUri($review), [
            'file' => $review['file'],
        ] + $reading);
    }

    /**
     * Accept a review's changes, read as given: the vocabulary's pending
     * namespace URI, its terms' changes, and how its file was read, together
     * or not at all. The review ends.
     *
     * @param string $token
     * @param array $reading
     * @throws ValidationException When the API refuses the pending
     *   namespace URI, or a term is too long to store; nothing is changed,
     *   and the review goes on
     */
    public function accept($token, array $reading)
    {
        $review = $this->get($token);
        if (!$review) {
            throw new ValidationException('This review has ended. Update the vocabulary\'s classes and properties to review its changes again.'); // @translate
        }
        $vocabulary = $this->apiManager->read('vocabularies', $review['vocabulary_id'])->getContent();
        // The importer writes to the vocabulary directly, without the API's
        // permission checks, so check them here.
        if ($vocabulary->isPermanent() || !$vocabulary->userIsAllowed('update')) {
            throw new PermissionDeniedException('User does not have permission to update this vocabulary');
        }
        $diff = $this->getDiff($review, $reading);
        $data = ['o:import_options' => array_intersect_key($reading, array_flip(RdfImporter::READING_OPTIONS))];
        if (null !== $review['namespace_uri']) {
            $data['o:namespace_uri'] = $review['namespace_uri'];
        }
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->apiManager->update('vocabularies', $review['vocabulary_id'], $data, [], ['isPartial' => true]);
            $this->rdfImporter->update($review['vocabulary_id'], $diff);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        $this->end($token);
    }

    /**
     * Cancel a review, changing nothing.
     *
     * @param string $token
     */
    public function cancel($token)
    {
        $this->end($token);
    }

    /**
     * Get where a review's Accept and Cancel go: its return URL, else the
     * vocabulary's page; without a review, the vocabularies.
     *
     * @param array|null $review
     * @return string
     */
    public function getReturnUrl(?array $review = null)
    {
        if (null === $review) {
            return $this->router->assemble(['controller' => 'vocabulary'], ['name' => 'admin/default']);
        }
        return $review['return_url'] ?? $this->router->assemble(
            ['controller' => 'vocabulary', 'id' => $review['vocabulary_id']],
            ['name' => 'admin/id']
        );
    }

    /**
     * Start a review, with its file written by a callback.
     *
     * @param int $vocabularyId
     * @param callable $write Writes the file to the path it's passed,
     *   returning whether it could
     * @param array $readingOptions
     * @param array $reviewOptions
     * @return string The review page's URL
     */
    protected function begin($vocabularyId, callable $write, array $readingOptions, array $reviewOptions)
    {
        $vocabulary = $this->apiManager->read('vocabularies', $vocabularyId)->getContent();
        $returnUrl = $reviewOptions['return_url'] ?? null;
        if (null !== $returnUrl && !$this->isLocalPath($returnUrl)) {
            throw new \InvalidArgumentException('A review\'s return URL must be a local path.');
        }
        $this->removeOldFiles();
        $path = tempnam($this->tempDir, self::FILE_PREFIX);
        if (false === $path) {
            throw new ValidationException('Could not read vocabulary file.'); // @translate
        }
        try {
            if (!$write($path)) {
                throw new ValidationException('Could not read vocabulary file.'); // @translate
            }
        } catch (\Exception $e) {
            unlink($path);
            throw $e;
        }
        $review = [
            'vocabulary_id' => $vocabulary->id(),
            'file' => $path,
            'reading' => [],
            'namespace_uri' => $reviewOptions['namespace_uri'] ?? null,
            'return_url' => $returnUrl,
            'source_label' => $reviewOptions['source_label'] ?? null,
        ];
        try {
            $review['reading'] = $this->getStartingReading($vocabulary, $review, $readingOptions);
            // A file that can't be read, has no terms in the namespace, or
            // has terms too long to store, is refused now rather than
            // reviewed. Without terms in the namespace, every term would seem
            // dropped from the file.
            $this->rdfImporter->checkNamespaceUri('file', $this->getNamespaceUri($review), [
                'file' => $path,
                'format' => $review['reading']['format'] ?? 'guess',
            ]);
            $this->getDiff($review, $review['reading']);
        } catch (\Exception $e) {
            unlink($path);
            throw $e;
        }
        $token = bin2hex(random_bytes(16));
        $this->getSession()->offsetSet($token, $review);
        return $this->router->assemble(
            ['controller' => 'vocabulary', 'id' => $vocabulary->id(), 'action' => 'review'],
            ['name' => 'admin/id', 'query' => ['review' => $token]]
        );
    }

    /**
     * Get how a review starts reading its file: as asked, else as the
     * vocabulary's file was read, else as it's guessed to have been read.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param array $review
     * @param array $readingOptions
     * @return array
     */
    protected function getStartingReading($vocabulary, array $review, array $readingOptions)
    {
        // An empty option, as an unchanged form posts one, isn't a choice.
        $reading = array_filter(
            array_intersect_key($readingOptions, array_flip(RdfImporter::READING_OPTIONS)),
            function ($value) {
                return null !== $value && '' !== $value;
            }
        );
        $stored = $vocabulary->importOptions();
        if (null !== $stored) {
            return $reading + $stored;
        }
        if (!isset($reading['lang']) || !isset($reading['comment_property'])) {
            $guess = $this->rdfImporter->guessReading($vocabulary->id(), 'file', $this->getNamespaceUri($review), [
                'file' => $review['file'],
            ] + $reading);
            $reading += $guess;
        }
        return $reading;
    }

    /**
     * End a review: forget it and remove its file.
     *
     * @param string $token
     */
    protected function end($token)
    {
        $session = $this->getSession();
        $review = $session->offsetGet((string) $token);
        if (is_array($review) && is_file($review['file'])) {
            unlink($review['file']);
        }
        $session->offsetUnset((string) $token);
    }

    /**
     * Remove reviews' files older than self::MAX_AGE, from reviews that
     * were never accepted or cancelled.
     */
    protected function removeOldFiles()
    {
        foreach (glob($this->tempDir . '/' . self::FILE_PREFIX . '*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < time() - self::MAX_AGE) {
                @unlink($path);
            }
        }
    }

    /**
     * Fetch the file at a URL with EasyRdf's HTTP client, asking for the
     * formats the importer reads, as EasyRdf does when it loads a URL.
     *
     * @param string $url
     * @return string
     */
    protected function fetch($url)
    {
        $accept = 'application/ld+json,application/rdf+xml;q=0.9,text/turtle;q=0.8,application/n-triples;q=0.7';
        $client = Http::getDefaultHttpClient();
        $client->resetParameters(true);
        $client->setUri($url);
        if ($client instanceof HttpClient) {
            $client->setHeaders('Accept', $accept);
        } else {
            $client->setHeaders(['Accept' => $accept]);
        }
        try {
            $response = $client->request('GET');
        } catch (\Exception $e) {
            throw new ValidationException('Could not load vocabulary from URL.'); // @translate
        }
        if (!$response->isSuccessful()) {
            throw new ValidationException('Could not load vocabulary from URL.'); // @translate
        }
        return $response->getBody();
    }

    /**
     * Check that a URL is a path on this site: it starts with one "/", and
     * has no scheme, host, or backslash.
     *
     * @param string $url
     * @return bool
     */
    protected function isLocalPath($url)
    {
        return is_string($url) && 1 === preg_match('#^/(?![/\\\\])[^\\\\\s]*$#', $url);
    }

    /**
     * Get the session container the reviews are kept in, starting it only
     * when a review needs it.
     *
     * @return Container
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = new Container('OmekaVocabularyReview');
        }
        return $this->session;
    }
}
