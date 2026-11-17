<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use Laminas\Router\RouteStackInterface;
use Laminas\Session\Container;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Permissions\Exception\PermissionDeniedException;

/**
 * Reviews of the changes a file would make to a vocabulary, held in the
 * session, with their files in the file store, until they're accepted or
 * cancelled.
 */
class VocabularyReview
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var VocabularyFileStore
     */
    protected $fileStore;

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
     * @var Container
     */
    protected $session;

    public function __construct(RdfImporter $rdfImporter, VocabularyFileStore $fileStore,
        ApiManager $apiManager, EntityManager $entityManager, RouteStackInterface $router
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->fileStore = $fileStore;
        $this->apiManager = $apiManager;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * Start a review of the changes a file would make to a vocabulary.
     *
     * @param int $vocabularyId
     * @param string $filePath The file, copied into the file store, so the
     *   caller may then delete it
     * @param array $readingOptions The reading options: format, lang,
     *   label_property, comment_property. Those not given, or empty, come from
     *   the vocabulary's import options, else from a guess
     *   (RdfImporter::guessReading()).
     * @param array $reviewOptions
     *   - namespace_uri: (optional) A pending namespace URI for the
     *     vocabulary, the one the file's terms use, accepted with the changes
     *   - return_url: (optional) Where Accept and Cancel go: a local path,
     *     such as "/admin/vocabulary". By default, the vocabulary's page.
     *   - source_label: (optional) What the review page calls the file, as
     *     in "Accepting makes the changes below, from %s". By default, "the
     *     file".
     * @return string The review page's URL
     * @throws ValidationException When the file can't be read, has no terms
     *   in the namespace, or has terms too long to store
     */
    public function start($vocabularyId, $filePath, array $readingOptions = [], array $reviewOptions = [])
    {
        $this->checkReturnUrl($reviewOptions);
        return $this->begin($vocabularyId, $this->fileStore->keep($filePath), $readingOptions, $reviewOptions);
    }

    /**
     * Start a review of the file at a URL, fetched once.
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
        $this->checkReturnUrl($reviewOptions);
        return $this->begin($vocabularyId, $this->fileStore->keepFromUrl($url), $readingOptions, $reviewOptions);
    }

    /**
     * Get a review.
     *
     * @param string $token
     * @return array|null vocabulary_id, file, file_token, reading,
     *   namespace_uri, return_url, source_label
     */
    public function get($token)
    {
        $review = $this->getSession()->offsetGet((string) $token);
        $file = is_array($review) ? $this->fileStore->getPath($review['file_token']) : null;
        if (null === $file) {
            return null;
        }
        return ['file' => $file] + $review;
    }

    /**
     * Get a review's reading options, with any the admin chose.
     *
     * @param array $review
     * @param array $choices Reading options; others are ignored
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
     * Get the changes a review's file would make, read with the given reading
     * options.
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
     * doesn't.
     *
     * @see RdfImporter::getMissingTerms()
     * @param array $review
     * @param array $reading
     * @return array
     */
    public function getMissingTerms(array $review, array $reading)
    {
        return $this->rdfImporter->getMissingTerms($review['vocabulary_id'], 'file', $this->getNamespaceUri($review), [
            'file' => $review['file'],
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
     * Accept a review's changes, with any pending namespace URI and the
     * reading options, in one transaction, and end the review.
     *
     * @param string $token
     * @param array $reading
     * @throws ValidationException When the review has ended, or the changes
     *   are refused; nothing is changed, and an open review stays open
     * @throws PermissionDeniedException When the user may not change the
     *   vocabulary
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
     * Get where a review's Accept and Cancel go.
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
     * Start a review of a file in the file store.
     *
     * @param int $vocabularyId
     * @param string $fileToken The file's token in the file store
     * @param array $readingOptions
     * @param array $reviewOptions
     * @return string The review page's URL
     */
    protected function begin($vocabularyId, $fileToken, array $readingOptions, array $reviewOptions)
    {
        $path = $this->fileStore->getPath($fileToken);
        $review = [
            'vocabulary_id' => null,
            'file' => $path,
            'file_token' => $fileToken,
            'reading' => [],
            'namespace_uri' => $reviewOptions['namespace_uri'] ?? null,
            'return_url' => $reviewOptions['return_url'] ?? null,
            'source_label' => $reviewOptions['source_label'] ?? null,
        ];
        try {
            $vocabulary = $this->apiManager->read('vocabularies', $vocabularyId)->getContent();
            $review['vocabulary_id'] = $vocabulary->id();
            $review['reading'] = $this->getStartingReading($vocabulary, $review, $readingOptions);
            // Without terms in the namespace, every term would seem dropped
            // from the file.
            $this->rdfImporter->checkNamespaceUri('file', $this->getNamespaceUri($review), [
                'file' => $path,
                'format' => $review['reading']['format'] ?? 'guess',
            ]);
            $this->getDiff($review, $review['reading']);
        } catch (\Exception $e) {
            $this->fileStore->remove($fileToken);
            throw $e;
        }
        unset($review['file']);
        $token = bin2hex(random_bytes(16));
        $this->getSession()->offsetSet($token, $review);
        return $this->router->assemble(
            ['controller' => 'vocabulary', 'id' => $vocabulary->id(), 'action' => 'review'],
            ['name' => 'admin/id', 'query' => ['review' => $token]]
        );
    }

    /**
     * Get how a review starts reading its file: as asked, else as stored,
     * else as guessed.
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
        if (is_array($review)) {
            $this->fileStore->remove($review['file_token']);
        }
        $session->offsetUnset((string) $token);
    }

    /**
     * Check that a review's return URL, if any, is a local path.
     *
     * @param array $reviewOptions
     * @throws \InvalidArgumentException
     */
    protected function checkReturnUrl(array $reviewOptions)
    {
        $returnUrl = $reviewOptions['return_url'] ?? null;
        if (null !== $returnUrl && !$this->isLocalPath($returnUrl)) {
            throw new \InvalidArgumentException('A review\'s return URL must be a local path.');
        }
    }

    /**
     * Check that a URL is a path on this host: one leading /, not followed by
     * another / or a \ (which browsers take for another host), and no \ or
     * space.
     *
     * @param string $url
     * @return bool
     */
    protected function isLocalPath($url)
    {
        return is_string($url) && 1 === preg_match('#^/(?![/\\\\])[^\\\\\s]*$#', $url);
    }

    /**
     * Get the session container, starting it only when needed.
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
