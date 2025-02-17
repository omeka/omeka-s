<?php
namespace Mapping\Spatial\ORM\Query\AST\Functions;

use LongitudeOne\Spatial\ORM\Query\AST\Functions\Standard\StBuffer as LongitudeOneStBuffer;

/**
 * Extend StBuffer to include MySQL as a compatible platform.
 */
class StBuffer extends LongitudeOneStBuffer
{
    protected function getPlatforms(): array
    {
        return ['postgresql', 'mysql'];
    }
}
