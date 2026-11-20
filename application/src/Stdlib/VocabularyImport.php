<?php
namespace Omeka\Stdlib;

use Laminas\Router\RouteStackInterface;
use Laminas\Session\Container;
use Omeka\Api\Exception\ValidationException;
use Omeka\File\StagedFiles;
use Omeka\Form\VocabularyForm;

/**
 * Starts imports, for core's Import and for a module that keeps vocabulary
 * files: it stages the file (StagedFiles) and sends the admin to the import
 * details page, which reads it. A module may also hand the import some of the
 * vocabulary's fields and the page's starting choices, its hand-off, held in
 * the session under the staged file's token until the import ends.
 */
class VocabularyImport
{
    /**
     * The vocabulary's fields a hand-off may fill in.
     */
    const FIELDS = ['o:label', 'o:comment', 'o:namespace_uri', 'o:prefix'];

    /**
     * The import options a hand-off may start the page's choices with. The
     * format goes in the page's URL.
     */
    const CHOICES = ['lang', 'label_property', 'comment_property'];

    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var StagedFiles
     */
    protected $stagedFiles;

    /**
     * @var RouteStackInterface
     */
    protected $router;

    /**
     * @var Container
     */
    protected $session;

    public function __construct(RdfImporter $rdfImporter, StagedFiles $stagedFiles, RouteStackInterface $router)
    {
        $this->rdfImporter = $rdfImporter;
        $this->stagedFiles = $stagedFiles;
        $this->router = $router;
    }

    /**
     * Start an import of a file on the import details page.
     *
     * @param string $filePath The file, of which a copy is staged, so the
     *   caller may then delete it
     * @param array $vocabulary (optional) o:label, o:comment, o:namespace_uri,
     *   and o:prefix, each filled in in place of what the file says
     * @param array $importOptions (optional) format, one the Import page
     *   offers (others are guessed); and lang, label_property, and
     *   comment_property, which the page's choices start with in place of its
     *   defaults
     * @return string The import details page's URL. The import then ends as
     *   any does, on the new vocabulary's page.
     * @throws ValidationException When the file can't be read, or has no
     *   classes or properties
     */
    public function start($filePath, array $vocabulary = [], array $importOptions = [])
    {
        try {
            $fileToken = $this->stagedFiles->stage($filePath);
        } catch (\Omeka\File\Exception\RuntimeException $e) {
            // Not with the staging failure's own message, which names a
            // path on the server.
            throw new ValidationException('Could not read vocabulary file.', 0, $e); // @translate
        }
        return $this->begin($fileToken, $vocabulary, $importOptions);
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
            $fileToken = $this->stagedFiles->stageContents($contents);
        } catch (\Omeka\File\Exception\RuntimeException $e) {
            throw new ValidationException('Could not read vocabulary file.', 0, $e); // @translate
        }
        // Staged, so it needn't stay in memory while the file is read.
        unset($contents);
        return $this->begin($fileToken, $vocabulary, $importOptions);
    }

    /**
     * Get an import: its staged file, and anything it was handed, as
     * VocabularyReview::get() gets a review.
     *
     * @param string $fileToken
     * @return array|null file, vocabulary, and import_options; null once the
     *   file is gone
     */
    public function get($fileToken)
    {
        $file = $this->stagedFiles->getPath($fileToken);
        if (null === $file) {
            return null;
        }
        $handOff = $this->getSession()->offsetGet((string) $fileToken);
        return [
            'file' => $file,
            'vocabulary' => $handOff['vocabulary'] ?? [],
            'import_options' => $handOff['import_options'] ?? [],
        ];
    }

    /**
     * End an import: forget what it was handed and remove its file.
     *
     * @param string $fileToken
     */
    public function end($fileToken)
    {
        $this->stagedFiles->delete($fileToken);
        $this->getSession()->offsetUnset((string) $fileToken);
    }

    /**
     * Begin an import of a staged file: refuse it now if it can't be read or has
     * no classes or properties, while the page that chose it can say so, and
     * hold anything handed with it.
     *
     * @param string $fileToken
     * @param array $vocabulary
     * @param array $importOptions
     * @return string The import details page's URL
     */
    protected function begin($fileToken, array $vocabulary, array $importOptions)
    {
        // As import details read it: a format their File format field doesn't
        // offer is guessed.
        $format = $importOptions['format'] ?? 'guess';
        if (!isset(VocabularyForm::FORMATS[$format])) {
            $format = 'guess';
        }
        try {
            $this->rdfImporter->getVocabularyDetails('file', ['file' => $this->stagedFiles->getPath($fileToken), 'format' => $format]);
        } catch (\Exception $e) {
            $this->stagedFiles->delete($fileToken);
            throw $e;
        }
        $handOff = [
            'vocabulary' => array_intersect_key($vocabulary, array_flip(self::FIELDS)),
            'import_options' => array_intersect_key($importOptions, array_flip(self::CHOICES)),
        ];
        if ($handOff['vocabulary'] || $handOff['import_options']) {
            $this->getSession()->offsetSet($fileToken, $handOff);
        }
        return $this->router->assemble(['controller' => 'vocabulary', 'action' => 'import-details'], [
            'name' => 'admin/default',
            'query' => ['file_token' => $fileToken, 'format' => $format],
        ]);
    }

    /**
     * Get the session container, starting it only when needed.
     *
     * @return Container
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = new Container('OmekaVocabularyImport');
        }
        return $this->session;
    }
}
