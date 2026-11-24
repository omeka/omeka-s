<?php
namespace Omeka\File;

/**
 * Stages files in the temporary directory for a later request to use. A
 * staged file's path stays on the server, never in a page: a workflow keeps it
 * with WorkflowStates, which deletes the file when the workflow ends. Files
 * older than self::MAX_AGE are deleted the next time a file is staged.
 */
class Stager
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

    public function __construct($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * Stage a copy of a file.
     *
     * @param string $filePath
     * @return string The staged file's path
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
     * @return string The staged file's path
     * @throws Exception\RuntimeException When the file can't be staged
     */
    public function stageContents($contents)
    {
        return $this->write(function ($path) use ($contents) {
            return false !== file_put_contents($path, $contents);
        });
    }

    /**
     * Stage a file written by a callback.
     *
     * @param callable $write Writes the file to the path it's passed,
     *   returning whether it could
     * @return string The staged file's path
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
        return $path;
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
}
