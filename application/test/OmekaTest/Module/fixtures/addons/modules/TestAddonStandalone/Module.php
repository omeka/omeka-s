<?php declare(strict_types=1);

namespace TestAddonStandalone;

use Omeka\Module\AbstractModule;

/**
 * Test addon module with standalone: true in composer.json.
 *
 * When standalone is true, the module uses its own vendor/ directory
 * instead of the main Omeka vendor/ directory.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return [];
    }
}
