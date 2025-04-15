<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function is_numeric;
use function strlen;

final class Upca implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return strlen($value) === 12;
    }

    public function hasValidCharacters(string $value): bool
    {
        return is_numeric($value);
    }

    public function hasValidChecksum(string $value): bool
    {
        return Util::gtin($value);
    }

    public function getLength(): int
    {
        return 12;
    }
}
