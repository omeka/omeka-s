<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

/**
 * @psalm-type AllowedLength = int|list<int>|'even'|'odd'|null
 */
interface AdapterInterface
{
    /**
     * Checks the length of a barcode
     */
    public function hasValidLength(string $value): bool;

    /**
     * Checks for allowed characters within the barcode
     */
    public function hasValidCharacters(string $value): bool;

    /**
     * Validates the checksum
     */
    public function hasValidChecksum(string $value): bool;

    /**
     * Returns the allowed barcode length
     *
     * @return AllowedLength
     */
    public function getLength();
}
