<?php declare(strict_types=1);

namespace TestAddonOverride;

use Omeka\Module\AbstractModule;

/**
 * Test addon module in composer-addons/modules/ to verify override priority.
 *
 * When a module with the same name exists in modules/, it should take
 * precedence over this version in composer-addons/modules/.
 */
class Module extends AbstractModule
{
    const VERSION = 'composer';

    public function getConfig()
    {
        return [];
    }
}
