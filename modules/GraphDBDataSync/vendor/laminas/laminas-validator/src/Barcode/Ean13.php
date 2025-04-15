<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function is_numeric;
use function strlen;

final class Ean13 implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return strlen($value) === 13;
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
        return 13;
    }
}
