<?php
namespace Omeka\File;

use Laminas\Session\Container;

/**
 * Files staged in the temporary directory between requests, for a later step
 * to use, under tokens kept in the session, so only the session that staged a
 * file can find it. Files older than self::MAX_AGE are deleted the next time
 * a file is staged.
 */
class StagedFiles
{
    /**
     * The start of staged files' names.
     */
    const FILE_PREFIX = 'omeka-staged-file-';

    /**
     * How long a staged file is kept, in seconds.
     */
    const MAX_AGE = 86400;

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
     * Stage a copy of a file.
     *
     * @param string $filePath
     * @return string The staged file's token
     * @throws Exception\RuntimeException When the file can't be read or staged
     */
    public function stage($filePath)
    {
        if (!is_readable($filePath)) {
            throw new Exception\RuntimeException(sprintf('Failed to read "%s".', $filePath));
        }
        return $this->write(function ($path) use ($filePath) {
            return copy($filePath, $path);
        });
    }

    /**
     * Stage a file with the given contents.
     *
     * @param string $contents
     * @return string The staged file's token
     * @throws Exception\RuntimeException When the file can't be staged
     */
    public function stageContents($contents)
    {
        return $this->write(function ($path) use ($contents) {
            return false !== file_put_contents($path, $contents);
        });
    }

    /**
     * Get the path of a staged file.
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
     * Delete a staged file.
     *
     * @param string $token
     */
    public function delete($token)
    {
        $session = $this->getSession();
        $path = $session->offsetGet((string) $token);
        if (is_string($path) && is_file($path)) {
            unlink($path);
        }
        $session->offsetUnset((string) $token);
    }

    /**
     * Stage a file written by a callback.
     *
     * @param callable $write Writes the file to the path it's passed,
     *   returning whether it could
     * @return string The staged file's token
     */
    protected function write(callable $write)
    {
        $this->deleteOldFiles();
        $path = tempnam($this->tempDir, self::FILE_PREFIX);
        if (false === $path) {
            throw new Exception\RuntimeException(sprintf('Failed to create a file in "%s".', $this->tempDir));
        }
        try {
            if (!$write($path)) {
                throw new Exception\RuntimeException(sprintf('Failed to write "%s".', $path));
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
     * Delete staged files older than self::MAX_AGE.
     */
    protected function deleteOldFiles()
    {
        foreach (glob($this->tempDir . '/' . self::FILE_PREFIX . '*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < time() - self::MAX_AGE) {
                @unlink($path);
            }
        }
    }

    /**
     * Get the session container, starting it only when needed.
     *
     * @return Container
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = new Container('OmekaStagedFiles');
        }
        return $this->session;
    }
}
