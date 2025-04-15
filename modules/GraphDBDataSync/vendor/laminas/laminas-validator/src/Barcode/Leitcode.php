<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function is_numeric;
use function strlen;

final class Leitcode implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return strlen($value) === 14;
    }

    public function hasValidCharacters(string $value): bool
    {
        return is_numeric($value);
    }

    public function hasValidChecksum(string $value): bool
    {
        return Util::identcode($value);
    }

    public function getLength(): int
    {
        return 14;
    }
}
