<?php
namespace Omeka\Stdlib;

use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use Laminas\Session\Container;
use Omeka\Api\Exception\ValidationException;

/**
 * Vocabulary files kept in the temporary directory between requests, under
 * tokens kept in the session. Files older than self::MAX_AGE are removed the
 * next time a file is kept.
 */
class VocabularyFileStore
{
    /**
     * The start of kept files' names.
     */
    const FILE_PREFIX = 'omeka-vocabulary-file-';

    /**
     * How long a kept file is kept, in seconds.
     */
    const MAX_AGE = 86400;

    /**
     * How many redirects fetching a URL follows: 5, as EasyRdf's HTTP client
     * follows by default.
     */
    const MAX_REDIRECTS = 5;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var Container
     */
    protected $session;

    public function __construct($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * Keep a copy of a file.
     *
     * @param string $filePath
     * @return string The kept file's token
     * @throws ValidationException When the file can't be read
     */
    public function keep($filePath)
    {
        if (!is_readable($filePath)) {
            throw new ValidationException('Could not read vocabulary file.'); // @translate
        }
        return $this->write(function ($path) use ($filePath) {
            return copy($filePath, $path);
        });
    }

    /**
     * Keep the file at a URL.
     *
     * @param string $url
     * @return string The kept file's token
     * @throws ValidationException When it can't be fetched
     */
    public function keepFromUrl($url)
    {
        return $this->write(function ($path) use ($url) {
            return false !== file_put_contents($path, $this->fetch($url));
        });
    }

    /**
     * Get the path of a kept file.
     *
     * @param string $token
     * @return string|null
     */
    public function getPath($token)
    {
        $path = $this->getSession()->offsetGet((string) $token);
        return is_string($path) && is_readable($path) ? $path : null;
    }

    /**
     * Remove a kept file.
     *
     * @param string $token
     */
    public function remove($token)
    {
        $session = $this->getSession();
        $path = $session->offsetGet((string) $token);
        if (is_string($path) && is_file($path)) {
            unlink($path);
        }
        $session->offsetUnset((string) $token);
    }

    /**
     * Keep a file written by a callback.
     *
     * @param callable $write Writes the file to the path it's passed,
     *   returning whether it could
     * @return string The kept file's token
     */
    protected function write(callable $write)
    {
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
        $token = bin2hex(random_bytes(16));
        $this->getSession()->offsetSet($token, $path);
        return $token;
    }

    /**
     * Remove kept files older than self::MAX_AGE.
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
     * Fetch the file at a URL with EasyRdf's HTTP client, asking for RDF
     * formats, as EasyRdf does when it loads a URL.
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
        // Follow redirects, as from purl.org or http to https. EasyRdf's
        // Graph::load() turns the shared client's following off, since it
        // follows them itself, and the setting outlasts it.
        if ($client instanceof HttpClient) {
            $client->setConfig(['maxredirects' => self::MAX_REDIRECTS]);
            $client->setHeaders('Accept', $accept);
        } else {
            $client->setOptions(['maxredirects' => self::MAX_REDIRECTS]);
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
     * Get the session container, starting it only when needed.
     *
     * @return Container
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = new Container('OmekaVocabularyFiles');
        }
        return $this->session;
    }
}
