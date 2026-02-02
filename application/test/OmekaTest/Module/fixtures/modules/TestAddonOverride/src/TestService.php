<?php declare(strict_types=1);

namespace TestAddonOverride;

/**
 * Test service class in modules/ (local version).
 *
 * This class should be loaded instead of the one in addons/modules/
 * when both exist, because modules/ takes precedence.
 */
class TestService
{
    const SOURCE = 'local';

    public function getSource(): string
    {
        return self::SOURCE;
    }
}
