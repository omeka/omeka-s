<?php declare(strict_types=1);

namespace TestAddonOverride;

use Omeka\Module\AbstractModule;

/**
 * Test module in modules/ that should override the composer version.
 *
 * This local version should take precedence over the version in
 * addons/modules/TestAddonOverride/.
 */
class Module extends AbstractModule
{
    const VERSION = 'local';

    public function getConfig()
    {
        return [];
    }
}
