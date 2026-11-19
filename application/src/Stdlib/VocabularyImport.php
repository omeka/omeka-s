<?php
namespace Omeka\Stdlib;

use Laminas\Router\RouteStackInterface;
use Laminas\Session\Container;
use Omeka\Form\VocabularyForm;

/**
 * Imports started on the import details page, by Import or by a module that
 * keeps vocabulary files: the file in the file store, and anything handed
 * with it for the page to start with, held in the session under the file's
 * token.
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
     * @var VocabularyFileStore
     */
    protected $fileStore;

    /**
     * @var RouteStackInterface
     */
    protected $router;

    /**
     * @var Container
     */
    protected $session;

    public function __construct(RdfImporter $rdfImporter, VocabularyFileStore $fileStore, RouteStackInterface $router)
    {
        $this->rdfImporter = $rdfImporter;
        $this->fileStore = $fileStore;
        $this->router = $router;
    }

    /**
     * Start an import of a file on the import details page.
     *
     * @param string $filePath The file, copied into the file store, so the
     *   caller may then delete it
     * @param array $vocabulary (optional) o:label, o:comment, o:namespace_uri,
     *   and o:prefix, each filled in in place of what the file says
     * @param array $importOptions (optional) format, one the Import page
     *   offers (others are guessed); and lang, label_property, and
     *   comment_property, which the page's choices start with in place of its
     *   defaults
     * @return string The import details page's URL. The import then ends as
     *   any does, on the new vocabulary's page.
     * @throws \Omeka\Api\Exception\ValidationException When the file can't be
     *   read, or has no classes or properties
     */
    public function start($filePath, array $vocabulary = [], array $importOptions = [])
    {
        return $this->begin($this->fileStore->keep($filePath), $vocabulary, $importOptions);
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
        return $this->begin($this->fileStore->keepFromUrl($url), $vocabulary, $importOptions);
    }

    /**
     * Get what an import of a kept file was handed, if anything.
     *
     * @param string $fileToken
     * @return array|null vocabulary and import_options
     */
    public function get($fileToken)
    {
        $import = $this->getSession()->offsetGet((string) $fileToken);
        return is_array($import) ? $import : null;
    }

    /**
     * End an import: forget what it was handed and remove its file.
     *
     * @param string $fileToken
     */
    public function end($fileToken)
    {
        $this->fileStore->remove($fileToken);
        $this->getSession()->offsetUnset((string) $fileToken);
    }

    /**
     * Begin an import of a kept file: refuse it now if it can't be read or has
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
            $this->rdfImporter->getVocabularyDetails('file', ['file' => $this->fileStore->getPath($fileToken), 'format' => $format]);
        } catch (\Exception $e) {
            $this->fileStore->remove($fileToken);
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
