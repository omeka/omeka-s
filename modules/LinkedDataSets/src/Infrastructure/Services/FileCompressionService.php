<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Services;

final class FileCompressionService
{
    /**
     * Compress a file using gzip
     *
     * Rewritten from Simon East's version here:
     * https://stackoverflow.com/a/22754032/3499843
     *
     * @param string $inFilename Input filename
     * @param int    $level      Compression level (default: 9)
     *
     * @throws \Exception if the input or output file can not be opened
     *
     * @return string Output filename
     */
    public function gzCompressFile(string $inFilename, int $level = 9): string
    {
        if ($this->isGzipFile($inFilename)) {
            throw new \Exception("File {$inFilename} is already compressed");
        }

        // Open input file
        $inFile = fopen($inFilename, "rb");
        if ($inFile === false) {
            throw new \Exception("Unable to open input file: $inFilename");
        }

        // Open output file
        $gzFilename = $inFilename . ".gz";
        $mode = "wb" . $level;
        $gzFile = gzopen($gzFilename, $mode);
        if ($gzFile === false) {
            fclose($inFile);
            throw new \Exception("Unable to open output file: $gzFilename");
        }

        // Stream copy
        $length = 512 * 1024; // 512 kB
        while (!feof($inFile)) {
            $stream = fread($inFile, $length);
            if ($stream === false) {
                throw new \Exception("An error occurred during reading: $inFile");
            }
            gzwrite($gzFile, $stream);
        }

        // Close files
        fclose($inFile);
        gzclose($gzFile);

        // Return the new filename
        return $gzFilename;
    }

    private function isGzipFile(string $path): bool
    {
        // the first the bytes of a gzip file are 1f 8b 08 according to
        // https://www.rfc-editor.org/rfc/rfc1952#page-6
        $handle = fopen($path, "rb");
        if ($handle === false) {
            throw new \Exception("Unable to open input file: $handle");
        }
        $bytes = fread($handle, 3);
        if ($bytes === false) {
            throw new \Exception("Inputfile {$handle} is empty");
        }
        fclose($handle);
        return bin2hex($bytes) === '1f8b08';
    }
}
