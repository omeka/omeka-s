<?php declare(strict_types=1);

namespace TestAddonBasic;

use Omeka\Module\AbstractModule;

/**
 * Test basic addon module in composer-addons/modules/.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return [];
    }
}
