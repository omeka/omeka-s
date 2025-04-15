<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function strlen;

/** @psalm-import-type AllowedLength from AdapterInterface */
final class Ean8 implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return strlen($value) === 7 || strlen($value) === 8;
    }

    public function hasValidCharacters(string $value): bool
    {
        return Util::stringMatchesAlphabet($value, '01234567890');
    }

    public function hasValidChecksum(string $value): bool
    {
        if (strlen($value) === 7) {
            return true;
        }

        return Util::gtin($value);
    }

    public function getLength(): int|string|array|null
    {
        return [7, 8];
    }
}
