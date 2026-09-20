<?php declare(strict_types=1);

namespace TestAddonOverride;

/**
 * Test service class in composer-addons/modules/ (composer version).
 *
 * This class should NOT be loaded when a local version exists in modules/,
 * because modules/ takes precedence over composer-addons/modules/.
 */
class TestService
{
    const SOURCE = 'addon';

    public function getSource(): string
    {
        return self::SOURCE;
    }
}
