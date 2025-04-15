<?php

declare(strict_types=1);

namespace Laminas\Validator\Barcode;

use function is_numeric;
use function strlen;

final class Code25interleaved implements AdapterInterface
{
    public function hasValidLength(string $value): bool
    {
        return (strlen($value) % 2) === 0;
    }

    public function hasValidCharacters(string $value): bool
    {
        return is_numeric($value);
    }

    public function hasValidChecksum(string $value): bool
    {
        return Util::code25($value);
    }

    public function getLength(): string
    {
        return 'even';
    }
}
