<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function is_numeric;

final class Code25 implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return true;
    }

    public function hasValidCharacters(string $value): bool
    {
        return is_numeric($value);
    }

    public function hasValidChecksum(string $value): bool
    {
        return Util::code25($value);
    }

    public function getLength(): int
    {
        return -1;
    }
}
