<?php declare(strict_types=1);

namespace Common;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

/**
 * Common module.
 *
 * Manage in one module all features that are commonly needed in other modules
 * but that are not available in the core.
 *
 * It bring together all one-time methods used to install or to config another
 * module. It replaces previous modules Generic, Next, and various controller
 * plugins and view helpers from many modules.
 *
 * This module is useless alone: it is designed to be used by other module.
 * See readme.
 *
 * @copyright Daniel Berthereau, 2018-2025
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return require __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);
        $this->fixIndexes();
        $this->checkGeneric();
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);
        $filepath = __DIR__ . '/data/scripts/upgrade.php';
        require_once $filepath;
    }

    /**
     * Early fix media_type, ingester and renderer indexes.
     *
     * See migration 20240219000000_AddIndexMediaType.
     */
    protected function fixIndexes(): void
    {
        // Early fix media_type index and other common indexes.
        // See migration 20240219000000_AddIndexMediaType.
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        $sqls = <<<'SQL'
ALTER TABLE `asset`
CHANGE `media_type` `media_type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `name`,
CHANGE `extension` `extension` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `storage_id`;

ALTER TABLE `job`
CHANGE `pid` `pid` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `owner_id`,
CHANGE `status` `status` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `pid`,
CHANGE `class` `class` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `status`;

ALTER TABLE `media`
CHANGE `ingester` `ingester` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `item_id`,
CHANGE `renderer` `renderer` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `ingester`,
CHANGE `media_type` `media_type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `source`,
CHANGE `extension` `extension` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `storage_id`;

ALTER TABLE `module`
CHANGE `version` `version` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `is_active`;

ALTER TABLE `resource`
CHANGE `resource_type` `resource_type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `modified`;

ALTER TABLE `resource_template_property`
CHANGE `default_lang` `default_lang` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `is_private`;

ALTER TABLE `value`
CHANGE `type` `type` varchar(190) COLLATE 'utf8mb4_unicode_ci' NOT NULL AFTER `value_resource_id`,
CHANGE `lang` `lang` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `type`;

SQL;
        foreach (explode(";\n\n", $sqls) as $sql) {
            try {
                $connection->executeStatement($sql);
            } catch (\Exception $e) {
                // Already done.
            }
        }

        if (version_compare(\Omeka\Module::VERSION, '4.1', '>=')) {
            $sql = <<<'SQL'
ALTER TABLE `site_page`
CHANGE `layout` `layout` varchar(190) COLLATE 'utf8mb4_unicode_ci' NULL AFTER `modified`;
SQL;
            try {
                $connection->executeStatement($sql);
            } catch (\Exception $e) {
                // Already done.
            }
        }

        $hasNewIndex = false;

        try {
            $connection->executeStatement('ALTER TABLE `fulltext_search` ADD INDEX `is_public` (`is_public`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }

        try {
            $connection->executeStatement('ALTER TABLE `media` ADD INDEX `ingester` (`ingester`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }
        try {
            $connection->executeStatement('ALTER TABLE `media` ADD INDEX `renderer` (`renderer`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }
        try {
            $connection->executeStatement('ALTER TABLE `media` ADD INDEX `media_type` (`media_type`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }
        try {
            $connection->executeStatement('ALTER TABLE `media` ADD INDEX `extension` (`extension`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }

        try {
            $connection->executeStatement('ALTER TABLE `resource` ADD INDEX `resource_type` (`resource_type`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }

        try {
            $connection->executeStatement('ALTER TABLE `value` ADD INDEX `type` (`type`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }
        try {
            $connection->executeStatement('ALTER TABLE `value` ADD INDEX `lang` (`lang`);');
            $hasNewIndex = true;
        } catch (\Exception $e) {
            // Index exists.
        }

        if ($hasNewIndex) {
            // Don't use a PsrMessage during install.
            $message = new \Omeka\Stdlib\Message(
                'Some indexes were added to tables to improve performance.' // @translate
            );
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $messenger->addSuccess($message);
        }
    }

    protected function checkGeneric(): void
    {
        $paths = glob(OMEKA_PATH . '/modules/*/src/Generic/AbstractModule.php');
        if (count($paths)) {
            return;
        }

        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');
        $connection->executeStatement('DELETE FROM module WHERE ID = "Generic";');

        // Don't use a PsrMessage during install.
        $message = new \Omeka\Stdlib\Message(
            'The module Generic is no longer needed and can be removed.' // @translate
        );
        $messenger = $services->get('ControllerPluginManager')->get('messenger');
        $messenger->addWarning($message);
    }
}
