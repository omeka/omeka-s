<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use Laminas\Router\RouteStackInterface;
use Omeka\Api\Adapter\VocabularyAdapter;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Manager as ApiManager;
use Omeka\Entity\Vocabulary;
use Omeka\File\Stager;
use Omeka\Permissions\Exception\PermissionDeniedException;

/**
 * Reviews of the changes a file would make to a vocabulary, each kept with
 * WorkflowStates from start() until it's accepted or canceled, or a day has
 * passed.
 */
class VocabularyReview
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var Stager
     */
    protected $stager;

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
     * @var VocabularyAdapter
     */
    protected $vocabularyAdapter;

    /**
     * @var WorkflowStates
     */
    protected $workflowStates;

    public function __construct(RdfImporter $rdfImporter, Stager $stager,
        ApiManager $apiManager, EntityManager $entityManager, RouteStackInterface $router,
        VocabularyAdapter $vocabularyAdapter, WorkflowStates $workflowStates
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->stager = $stager;
        $this->apiManager = $apiManager;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->vocabularyAdapter = $vocabularyAdapter;
        $this->workflowStates = $workflowStates;
    }

    /**
     * Start a review of the changes a file would make to a vocabulary.
     *
     * @param int $vocabularyId
     * @param string $filePath The file, of which a copy is staged, so the
     *   caller may then delete it
     * @param array $importOptions The import options: format, lang,
     *   label_property, comment_property. Null is the default chosen (First
     *   available, or the default property); those not given, or "", come
     *   from the vocabulary's import options, else from a guess
     *   (RdfImporter::guessImportOptions()).
     * @param array $reviewOptions
     *   - new_namespace_uri: (optional) A new namespace URI for the
     *     vocabulary, the one the file's terms use, accepted with the changes:
     *     another form of the vocabulary's own, such as https for http, that
     *     no other vocabulary has
     * @return string The review page's URL. Accept and Cancel then go to the
     *   vocabulary's page.
     * @throws PermissionDeniedException When the user may not change the
     *   vocabulary
     * @throws \InvalidArgumentException When the new namespace URI isn't a
     *   form of the vocabulary's
     * @throws ValidationException When another vocabulary has the new
     *   namespace URI, or the file can't be read, has no terms in the
     *   namespace, or has terms too long to store
     */
    public function start($vocabularyId, $filePath, array $importOptions = [], array $reviewOptions = [])
    {
        $vocabulary = $this->getVocabularyToReview($vocabularyId, $reviewOptions);
        try {
            $file = $this->stager->stage($filePath);
        } catch (\Omeka\File\Exception\RuntimeException $e) {
            // Not with the staging failure's own message, which names a
            // path on the server.
            throw new ValidationException('Could not read vocabulary file.', 0, $e); // @translate
        }
        return $this->begin($file, $vocabulary, $importOptions, $reviewOptions);
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
        $vocabulary = $this->getVocabularyToReview($vocabularyId, $reviewOptions);
        $contents = $this->rdfImporter->fetchFile($url);
        try {
            $file = $this->stager->stageContents($contents);
        } catch (\Omeka\File\Exception\RuntimeException $e) {
            throw new ValidationException('Could not read vocabulary file.', 0, $e); // @translate
        }
        // Staged, so it needn't stay in memory while the file is read.
        unset($contents);
        return $this->begin($file, $vocabulary, $importOptions, $reviewOptions);
    }

    /**
     * Get a review.
     *
     * @param string $token
     * @return array|null token, files (source, the staged file's path),
     *   vocabulary_id, import_options, and new_namespace_uri; null once the
     *   review has ended
     */
    public function get($token)
    {
        return $this->workflowStates->get($token);
    }

    /**
     * Get a review's import options, with any the admin chose.
     *
     * @param array $review
     * @param array $chosen Import options chosen; other keys are ignored
     * @return array
     */
    public function getImportOptions(array $review, array $chosen = [])
    {
        return array_intersect_key($chosen, array_flip(Vocabulary::IMPORT_OPTIONS)) + $review['import_options'];
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
            'file' => $review['files']['source'],
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
            'file' => $review['files']['source'],
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
            'file' => $review['files']['source'],
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
            throw new ValidationException('This review has ended.'); // @translate
        }
        // The importer writes to the vocabulary directly, without the API's
        // permission checks, so check them here, as start() did.
        $this->getVocabularyToReview($review['vocabulary_id'], []);
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
        $this->workflowStates->delete($token);
    }

    /**
     * Cancel a review, changing nothing.
     *
     * @param string $token
     */
    public function cancel($token)
    {
        $this->workflowStates->delete($token);
    }

    /**
     * Get the vocabulary a review is to change, first checking that the user
     * may change it, and that any new namespace URI is another form of its own
     * that no other vocabulary has.
     *
     * @param int $vocabularyId
     * @param array $reviewOptions
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     * @throws PermissionDeniedException
     * @throws \InvalidArgumentException
     * @throws ValidationException
     */
    protected function getVocabularyToReview($vocabularyId, array $reviewOptions)
    {
        $vocabulary = $this->apiManager->read('vocabularies', $vocabularyId)->getContent();
        if (!$vocabulary->userMayUpdate()) {
            throw new PermissionDeniedException('User does not have permission to update this vocabulary');
        }
        $newNamespaceUri = $reviewOptions['new_namespace_uri'] ?? null;
        if (null === $newNamespaceUri || $newNamespaceUri === $vocabulary->namespaceUri()) {
            return $vocabulary;
        }
        // Compared both ways: http://example.org/ns// has
        // http://example.org/ns# among its forms, but not the other way.
        if (!$this->isNamespaceUriFormOf($newNamespaceUri, $vocabulary->namespaceUri())
            && !$this->isNamespaceUriFormOf($vocabulary->namespaceUri(), $newNamespaceUri)
        ) {
            throw new \InvalidArgumentException('A review\'s new namespace URI must be another form of the vocabulary\'s.');
        }
        // A change only in capitals isn't a new namespace URI to the API.
        if (strtolower($newNamespaceUri) === strtolower($vocabulary->namespaceUri())) {
            return $vocabulary;
        }
        // Refused now, while the page that chose the file can say why, as the
        // API would refuse it at Accept: another vocabulary has exactly it.
        $other = $this->vocabularyAdapter->findByNamespaceUri($newNamespaceUri, $vocabulary->id());
        if ($other && strtolower($other->getNamespaceUri()) === strtolower($newNamespaceUri)) {
            $exception = new ValidationException;
            $exception->getErrorStore()->addError('o:namespace_uri', new Message(
                'The namespace URI "%s" is already taken.', // @translate
                $newNamespaceUri
            ));
            throw $exception;
        }
        return $vocabulary;
    }

    /**
     * Whether a URI is one of a namespace URI's forms
     * (Vocabulary::getNamespaceUriForms()), ignoring capitals.
     *
     * @param string $uri
     * @param string $namespaceUri
     * @return bool
     */
    protected function isNamespaceUriFormOf($uri, $namespaceUri)
    {
        $forms = array_map('strtolower', Vocabulary::getNamespaceUriForms($namespaceUri));
        return in_array(strtolower($uri), $forms, true);
    }

    /**
     * Start a review of a staged file.
     *
     * @param string $file The staged file's path
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param array $importOptions
     * @param array $reviewOptions
     * @return string The review page's URL
     */
    protected function begin($file, $vocabulary, array $importOptions, array $reviewOptions)
    {
        $newNamespaceUri = $reviewOptions['new_namespace_uri'] ?? null;
        $review = [
            'vocabulary_id' => $vocabulary->id(),
            // As get() gives it, for the methods that read the file.
            'files' => ['source' => $file],
            'import_options' => [],
            // The vocabulary's own is no change.
            'new_namespace_uri' => $newNamespaceUri === $vocabulary->namespaceUri() ? null : $newNamespaceUri,
        ];
        try {
            $review['import_options'] = $this->getStartingImportOptions($vocabulary, $review, $importOptions);
            // Without terms in the namespace, every term would seem dropped
            // from the file.
            $this->rdfImporter->checkHasMembers('file', $this->getNamespaceUri($review), [
                'file' => $file,
                'format' => $review['import_options']['format'] ?? 'guess',
            ]);
            $this->getDiff($review, $review['import_options']);
        } catch (\Exception $e) {
            unlink($file);
            throw $e;
        }
        unset($review['files']);
        $token = $this->workflowStates->create($review, ['source' => $file]);
        return $this->router->assemble(
            ['controller' => 'vocabulary', 'id' => $vocabulary->id(), 'action' => 'review'],
            ['name' => 'admin/id', 'query' => ['token' => $token]]
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
        // "" isn't a choice; null is the default, chosen.
        $importOptions = [];
        foreach ($given as $key => $value) {
            if (in_array($key, Vocabulary::IMPORT_OPTIONS, true) && '' !== $value) {
                $importOptions[$key] = $value;
            }
        }
        $stored = $vocabulary->importOptions();
        if (null !== $stored) {
            return $importOptions + $stored;
        }
        if (!array_key_exists('lang', $importOptions) || !array_key_exists('comment_property', $importOptions)) {
            $guess = $this->rdfImporter->guessImportOptions('file', $this->getNamespaceUri($review), [
                'file' => $review['files']['source'],
                'vocabulary_id' => $vocabulary->id(),
            ] + $importOptions);
            $importOptions += $guess;
        }
        return $importOptions;
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
}
