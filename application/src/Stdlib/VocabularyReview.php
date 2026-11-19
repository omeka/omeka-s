<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use Laminas\Router\RouteStackInterface;
use Laminas\Session\Container;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\Vocabulary;
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
     * @param array $importOptions The import options: format, lang,
     *   label_property, comment_property. Those not given, or empty, come from
     *   the vocabulary's import options, else from a guess
     *   (RdfImporter::guessImportOptions()).
     * @param array $reviewOptions
     *   - new_namespace_uri: (optional) A new namespace URI for the
     *     vocabulary, the one the file's terms use, accepted with the changes
     * @return string The review page's URL. Accept and Cancel then go to the
     *   vocabulary's page.
     * @throws ValidationException When the file can't be read, has no terms
     *   in the namespace, or has terms too long to store
     */
    public function start($vocabularyId, $filePath, array $importOptions = [], array $reviewOptions = [])
    {
        return $this->begin($vocabularyId, $this->fileStore->keep($filePath), $importOptions, $reviewOptions);
    }

    /**
     * Start a review of the file at a URL, fetched once.
     *
     * @see self::start()
     * @param int $vocabularyId
     * @param string $url
     * @param array $importOptions
     * @param array $reviewOptions
     * @return string The review page's URL
     */
    public function startFromUrl($vocabularyId, $url, array $importOptions = [], array $reviewOptions = [])
    {
        return $this->begin($vocabularyId, $this->fileStore->keepFromUrl($url), $importOptions, $reviewOptions);
    }

    /**
     * Get a review.
     *
     * @param string $token
     * @return array|null vocabulary_id, file, file_token, import_options,
     *   new_namespace_uri
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
     * Get a review's import options, with any the admin chose.
     *
     * @param array $review
     * @param array $choices Import options; others are ignored
     * @return array
     */
    public function getImportOptions(array $review, array $choices = [])
    {
        return array_intersect_key($choices, array_flip(Vocabulary::IMPORT_OPTIONS)) + $review['import_options'];
    }

    /**
     * Get the namespace URI a review reads its file with: the new one, if
     * any, else the vocabulary's.
     *
     * @param array $review
     * @return string
     */
    protected function getNamespaceUri(array $review)
    {
        return $review['new_namespace_uri']
            ?? $this->apiManager->read('vocabularies', $review['vocabulary_id'])->getContent()->namespaceUri();
    }

    /**
     * Get the changes a review's file would make, read with the given import
     * options.
     *
     * @see RdfImporter::getDiff()
     * @param array $review
     * @param array $importOptions
     * @return array
     */
    public function getDiff(array $review, array $importOptions)
    {
        return $this->rdfImporter->getDiff('file', $this->getNamespaceUri($review), [
            'file' => $review['file'],
            'vocabulary_id' => $review['vocabulary_id'],
        ] + $importOptions);
    }

    /**
     * Get the classes and properties the vocabulary has that a review's file
     * doesn't.
     *
     * @see RdfImporter::getMembersNotInFile()
     * @param array $review
     * @param array $importOptions
     * @return array
     */
    public function getMembersNotInFile(array $review, array $importOptions)
    {
        return $this->rdfImporter->getMembersNotInFile('file', $this->getNamespaceUri($review), [
            'file' => $review['file'],
            'vocabulary_id' => $review['vocabulary_id'],
        ] + $importOptions);
    }

    /**
     * Get the languages of a review's file.
     *
     * @see RdfImporter::getLanguages()
     * @param array $review
     * @param array $importOptions
     * @return string[]
     */
    public function getLanguages(array $review, array $importOptions)
    {
        return $this->rdfImporter->getLanguages('file', $this->getNamespaceUri($review), [
            'file' => $review['file'],
        ] + $importOptions);
    }

    /**
     * Accept a review's changes, with any new namespace URI and the
     * import options, in one transaction, and end the review.
     *
     * @param string $token
     * @param array $importOptions
     * @throws ValidationException When the review has ended, or the changes
     *   are refused; nothing is changed, and an open review stays open
     * @throws PermissionDeniedException When the user may not change the
     *   vocabulary
     */
    public function accept($token, array $importOptions)
    {
        $review = $this->get($token);
        if (!$review) {
            throw new ValidationException('This review has ended. To review the changes again, use Update classes and properties.'); // @translate
        }
        $vocabulary = $this->apiManager->read('vocabularies', $review['vocabulary_id'])->getContent();
        // The importer writes to the vocabulary directly, without the API's
        // permission checks, so check them here.
        if (!$vocabulary->userMayUpdate()) {
            throw new PermissionDeniedException('User does not have permission to update this vocabulary');
        }
        $diff = $this->getDiff($review, $importOptions);
        $data = ['o:import_options' => array_intersect_key($importOptions, array_flip(Vocabulary::IMPORT_OPTIONS))];
        if (null !== $review['new_namespace_uri']) {
            $data['o:namespace_uri'] = $review['new_namespace_uri'];
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
     * Start a review of a file in the file store.
     *
     * @param int $vocabularyId
     * @param string $fileToken The file's token in the file store
     * @param array $importOptions
     * @param array $reviewOptions
     * @return string The review page's URL
     */
    protected function begin($vocabularyId, $fileToken, array $importOptions, array $reviewOptions)
    {
        $path = $this->fileStore->getPath($fileToken);
        $review = [
            'vocabulary_id' => null,
            'file' => $path,
            'file_token' => $fileToken,
            'import_options' => [],
            'new_namespace_uri' => $reviewOptions['new_namespace_uri'] ?? null,
        ];
        try {
            $vocabulary = $this->apiManager->read('vocabularies', $vocabularyId)->getContent();
            $review['vocabulary_id'] = $vocabulary->id();
            $review['import_options'] = $this->getStartingImportOptions($vocabulary, $review, $importOptions);
            // Without terms in the namespace, every term would seem dropped
            // from the file.
            $this->rdfImporter->checkHasMembers('file', $this->getNamespaceUri($review), [
                'file' => $path,
                'format' => $review['import_options']['format'] ?? 'guess',
            ]);
            $this->getDiff($review, $review['import_options']);
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
     * Get the import options a review starts with: as given, else as stored,
     * else as guessed.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param array $review
     * @param array $given The import options given to start the review with
     * @return array
     */
    protected function getStartingImportOptions($vocabulary, array $review, array $given)
    {
        // An empty option, as an unchanged form posts one, isn't a choice.
        $importOptions = array_filter(
            array_intersect_key($given, array_flip(Vocabulary::IMPORT_OPTIONS)),
            function ($value) {
                return null !== $value && '' !== $value;
            }
        );
        $stored = $vocabulary->importOptions();
        if (null !== $stored) {
            return $importOptions + $stored;
        }
        if (!isset($importOptions['lang']) || !isset($importOptions['comment_property'])) {
            $guess = $this->rdfImporter->guessImportOptions('file', $this->getNamespaceUri($review), [
                'file' => $review['file'],
                'vocabulary_id' => $vocabulary->id(),
            ] + $importOptions);
            $importOptions += $guess;
        }
        return $importOptions;
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
