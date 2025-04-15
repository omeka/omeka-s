<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function strlen;

/** @psalm-import-type AllowedLength from AdapterInterface */
final class Ean5 implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return strlen($value) === 5;
    }

    public function hasValidCharacters(string $value): bool
    {
        return Util::stringMatchesAlphabet($value, '0123456789');
    }

    public function hasValidChecksum(string $value): bool
    {
        return true;
    }

    public function getLength(): int|string|array|null
    {
        return 5;
    }
}
