<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

final class Code39ext implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return true;
    }

    public function hasValidCharacters(string $value): bool
    {
        return Util::isAscii128($value);
    }

    public function hasValidChecksum(string $value): bool
    {
        return true;
    }

    public function getLength(): int
    {
        return -1;
    }
}
