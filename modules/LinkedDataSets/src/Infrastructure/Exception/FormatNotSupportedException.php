<?php

declare(strict_types=1);

namespace LinkedDataSets\Infrastructure\Exception;

final class FormatNotSupportedException extends \Exception
{
    public static function withFormat($format): self
    {
        return new self("encodingFormat {$format} is not supported");
    }
}
