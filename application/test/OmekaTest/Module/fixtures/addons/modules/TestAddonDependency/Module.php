<?php declare(strict_types=1);

namespace TestAddonDependency;

use Laminas\Json\Json;
use Omeka\Module\AbstractModule;

/**
 * Test addon module with a dependency on laminas/laminas-json.
 *
 * This module verifies that composer dependencies are properly handled
 * when a module is installed via composer in addons/modules/.
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return [];
    }

    /**
     * Example method using the laminas-json dependency.
     */
    public function encodeData(array $data): string
    {
        return Json::encode($data);
    }
}
