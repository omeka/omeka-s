<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function in_array;
use function is_numeric;
use function strlen;

final class Postnet implements AdapterInterface
{
    private const LENGTH = [6, 7, 10, 12];

    public function hasValidLength(string $value): bool
    {
        return in_array(strlen($value), self::LENGTH, true);
    }

    public function hasValidCharacters(string $value): bool
    {
        return is_numeric($value);
    }

    public function hasValidChecksum(string $value): bool
    {
        return Util::postnet($value);
    }

    public function getLength(): array
    {
        return self::LENGTH;
    }
}
