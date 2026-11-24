<?php
namespace Omeka\Stdlib;

use Laminas\Router\RouteStackInterface;
use Omeka\Api\Adapter\VocabularyAdapter;
use Omeka\Api\Exception\ValidationException;
use Omeka\File\Stager;

/**
 * Imports of vocabulary files, each kept with WorkflowStates from start() until
 * it's imported or canceled, or a day has passed. A module may hand an import
 * the vocabulary's fields and the page's starting choices.
 */
class VocabularyImport
{
    /**
     * The vocabulary's fields a hand-off may fill in.
     */
    const FIELDS = ['o:label', 'o:comment', 'o:namespace_uri', 'o:prefix'];

    /**
     * The import options a hand-off may start the page's choices with.
     */
    const CHOICES = ['lang', 'label_property', 'comment_property'];

    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var Stager
     */
    protected $stager;

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

    public function __construct(RdfImporter $rdfImporter, Stager $stager, RouteStackInterface $router,
        VocabularyAdapter $vocabularyAdapter, WorkflowStates $workflowStates
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->stager = $stager;
        $this->router = $router;
        $this->vocabularyAdapter = $vocabularyAdapter;
        $this->workflowStates = $workflowStates;
    }

    /**
     * Start an import of a file on the import details page.
     *
     * @param string $filePath The file, of which a copy is staged, so the
     *   caller may then delete it
     * @param array $vocabulary (optional) o:label, o:comment, o:namespace_uri,
     *   and o:prefix, each filled in in place of what the file says
     * @param array $importOptions (optional) format, one of
     *   RdfImporter::FORMATS, else it's guessed; and lang, label_property, and
     *   comment_property, which the page's choices start with
     * @return string The import details page's URL. The import then ends as
     *   any does, on the new vocabulary's page.
     * @throws ValidationException When the file can't be read, or has no
     *   classes or properties
     */
    public function start($filePath, array $vocabulary = [], array $importOptions = [])
    {
        try {
            $file = $this->stager->stage($filePath);
        } catch (\Omeka\File\Exception\RuntimeException $e) {
            // Not with the staging failure's own message, which names a
            // path on the server.
            throw new ValidationException('Could not read vocabulary file.', 0, $e); // @translate
        }
        return $this->begin($file, $vocabulary, $importOptions);
    }

    /**
     * Start an import of the file at a URL, fetched once.
     *
     * @see self::start()
     * @param string $url
     * @param array $vocabulary
     * @param array $importOptions
     * @return string The import details page's URL
     */
    public function startFromUrl($url, array $vocabulary = [], array $importOptions = [])
    {
        $contents = $this->rdfImporter->fetchFile($url);
        try {
            $file = $this->stager->stageContents($contents);
        } catch (\Omeka\File\Exception\RuntimeException $e) {
            throw new ValidationException('Could not read vocabulary file.', 0, $e); // @translate
        }
        // Staged, so it needn't stay in memory while the file is read.
        unset($contents);
        return $this->begin($file, $vocabulary, $importOptions);
    }

    /**
     * Get an import: its staged file and anything it was handed.
     *
     * @param string $token
     * @return array|null token, files (source, the staged file's path),
     *   vocabulary, and import_options; null once the import has ended
     */
    public function get($token)
    {
        return $this->workflowStates->get($token);
    }

    /**
     * Get what an import's file says of its vocabulary.
     *
     * @see RdfImporter::getVocabularyDetails()
     * @param array $import As get() gives it
     * @param string|null $lang The language to prefer, such as the
     *   installation's locale
     * @return array
     */
    public function getFileDetails(array $import, $lang = null)
    {
        return $this->rdfImporter->getVocabularyDetails('file', [
            'file' => $import['files']['source'],
            'format' => $import['import_options']['format'],
            'lang' => $lang,
        ]);
    }

    /**
     * Get the vocabulary Omeka has under any form of a namespace URI.
     *
     * @param string $namespaceUri
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    public function findVocabulary($namespaceUri)
    {
        if ('' === (string) $namespaceUri) {
            return null;
        }
        $vocabulary = $this->vocabularyAdapter->findByNamespaceUri($namespaceUri);
        if (!$vocabulary) {
            return null;
        }
        return $this->vocabularyAdapter->getRepresentation($vocabulary);
    }

    /**
     * Get the fields import details start with: what the file says of its
     * vocabulary, with any a module handed the import in their place.
     *
     * @param array $import As get() gives it
     * @param array $fileDetails As getFileDetails() gives them
     * @return array o:label, o:comment, o:namespace_uri, and o:prefix
     */
    public function getStartingFields(array $import, array $fileDetails)
    {
        return array_replace([
            'o:label' => $fileDetails['label'],
            'o:comment' => $fileDetails['comment'],
            'o:namespace_uri' => $fileDetails['namespace_uri'],
            'o:prefix' => $fileDetails['prefix'],
        ], $import['vocabulary']);
    }

    /**
     * Get the choices import details start with: the file's language nearest
     * the installation's, or how an updatable vocabulary's file was last read,
     * so Review changes reads it the same way; then any a module handed the
     * import.
     *
     * @param array $import As get() gives it
     * @param array $fileDetails As getFileDetails() gives them
     * @param \Omeka\Api\Representation\VocabularyRepresentation|null $updatable
     * @return array
     */
    public function getStartingChoices(array $import, array $fileDetails, $updatable)
    {
        // Null is First available.
        $startingChoices = ['lang' => $fileDetails['lang'] ?? null];
        $stored = $updatable ? $updatable->importOptions() : null;
        if (null !== $stored) {
            // Null for a choice not stored, which starts its field on the
            // default.
            foreach (self::CHOICES as $choice) {
                $startingChoices[$choice] = $stored[$choice] ?? null;
            }
        }
        return array_replace($startingChoices, $import['import_options']);
    }

    /**
     * Get an import's import options: its format, and each choice, null where
     * none was made.
     *
     * @param array $import As get() gives it
     * @param array $chosen The import options chosen on the page, each left
     *   out where none was made
     * @return array format, lang, label_property, comment_property
     */
    public function getImportOptions(array $import, array $chosen)
    {
        $importOptions = ['format' => $import['import_options']['format']];
        foreach (self::CHOICES as $choice) {
            $importOptions[$choice] = $chosen[$choice] ?? null;
        }
        return $importOptions;
    }

    /**
     * Get the classes and properties importing would add, refusing a file
     * with none in the namespace.
     *
     * @param array $import As get() gives it
     * @param string $namespaceUri
     * @param array $importOptions format, lang, label_property, comment_property
     * @return array Labels and comments by local name, by kind
     * @throws ValidationException When the file has no classes or properties
     *   in the namespace
     */
    public function getMembers(array $import, $namespaceUri, array $importOptions)
    {
        $options = ['file' => $import['files']['source']] + $importOptions;
        $this->rdfImporter->checkHasMembers('file', $namespaceUri, $options);
        return $this->rdfImporter->getMembers('file', $namespaceUri, $options);
    }

    /**
     * Import the vocabulary and end the import.
     *
     * @param string $token
     * @param array $vocabulary o:label, o:comment, o:namespace_uri, o:prefix;
     *   other keys, such as a form's CSRF field, are left out
     * @param array $importOptions format, lang, label_property, comment_property
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     * @throws ValidationException When the import has ended, or is refused,
     *   leaving the import open
     */
    public function import($token, array $vocabulary, array $importOptions)
    {
        $import = $this->get($token);
        if (!$import) {
            throw new ValidationException('This import has ended.'); // @translate
        }
        // Only the vocabulary's own fields, since the importer hands the API
        // all it's given.
        $vocabulary = array_intersect_key($vocabulary, array_flip(self::FIELDS));
        $response = $this->rdfImporter->import('file', $vocabulary, ['file' => $import['files']['source']] + $importOptions);
        $this->workflowStates->delete($token);
        return $response->getContent();
    }

    /**
     * Cancel an import, importing nothing.
     *
     * @param string $token
     */
    public function cancel($token)
    {
        $this->workflowStates->delete($token);
    }

    /**
     * Begin an import of a staged file, refusing it now if it can't be read or
     * has no classes or properties, while the page that chose it can say so.
     *
     * @param string $file The staged file's path
     * @param array $vocabulary
     * @param array $importOptions
     * @return string The import details page's URL
     */
    protected function begin($file, array $vocabulary, array $importOptions)
    {
        $format = $importOptions['format'] ?? 'guess';
        if (!in_array($format, RdfImporter::FORMATS, true)) {
            $format = 'guess';
        }
        try {
            $this->rdfImporter->getVocabularyDetails('file', ['file' => $file, 'format' => $format]);
        } catch (\Exception $e) {
            unlink($file);
            throw $e;
        }
        $token = $this->workflowStates->create([
            'vocabulary' => array_intersect_key($vocabulary, array_flip(self::FIELDS)),
            'import_options' => ['format' => $format] + array_intersect_key($importOptions, array_flip(self::CHOICES)),
        ], ['source' => $file]);
        return $this->router->assemble(['controller' => 'vocabulary', 'action' => 'import-details'], [
            'name' => 'admin/default',
            'query' => ['token' => $token],
        ]);
    }
}
