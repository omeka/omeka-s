<?php
namespace Omeka\Stdlib;

use Laminas\Router\RouteStackInterface;
use Laminas\Session\Container;

/**
 * Imports handed to the import details page, as a module hands it a
 * vocabulary file it keeps: the file in the file store, and what the page
 * starts with, held in the session under the file's token.
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

    public function __construct(VocabularyFileStore $fileStore, RouteStackInterface $router)
    {
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
     * @param array $importOptions (optional) format, and lang,
     *   label_property, and comment_property, which the page's choices start
     *   with in place of its defaults
     * @return string The import details page's URL. The import then ends as
     *   any does, on the new vocabulary's page.
     * @throws \Omeka\Api\Exception\ValidationException When the file can't be
     *   read
     */
    public function start($filePath, array $vocabulary = [], array $importOptions = [])
    {
        $token = $this->fileStore->keep($filePath);
        $this->getSession()->offsetSet($token, [
            'vocabulary' => array_intersect_key($vocabulary, array_flip(self::FIELDS)),
            'import_options' => array_intersect_key($importOptions, array_flip(self::CHOICES)),
        ]);
        return $this->router->assemble(['controller' => 'vocabulary', 'action' => 'import-details'], [
            'name' => 'admin/default',
            'query' => ['file_token' => $token, 'format' => $importOptions['format'] ?? 'guess'],
        ]);
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
     * Forget what an import was handed, once it's done.
     *
     * @param string $fileToken
     */
    public function end($fileToken)
    {
        $this->getSession()->offsetUnset((string) $fileToken);
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
