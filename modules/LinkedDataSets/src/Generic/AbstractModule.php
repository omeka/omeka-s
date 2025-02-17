<?php

declare(strict_types=1);

/*
 * Copyright Daniel Berthereau, 2018-2023
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Generic;

use Laminas\EventManager\Event;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Settings\SettingsInterface;
use Omeka\Stdlib\Message;

/**
 * This class allows to manage all methods that should run only once and that
 * are generic to all modules (install and settings).
 *
 * The logic is "config over code": so all settings have just to be set in the
 * main `config/module.config.php` file, inside a key with the lowercase module
 * name,  with sub-keys `config`, `settings`, `site_settings`, `user_settings`
 * and `block_settings`. All the forms have just to be standard Zend form.
 * Eventual install and uninstall sql can be set in `data/install/` and upgrade
 * code in `data/scripts`.
 *
 * See readme.
 */
abstract class AbstractModule extends \Omeka\Module\AbstractModule
{
    public function getConfig()
    {
        return include $this->modulePath() . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        // Check last version of modules.
        $sharedEventManager = $this->getServiceLocator()->get('SharedEventManager');
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Module',
            'view.browse.after',
            [$this, 'checkAddonVersions']
        );
    }

    public function install(ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);
        $translator = $services->get('MvcTranslator');
        $this->preInstall();
        if (!$this->checkDependency()) {
            $message = new Message(
                $translator->translate('This module requires the module "%s".'), // @translate
                $this->dependency
            );
            throw new ModuleCannotInstallException((string) $message);
        }
        if (!$this->checkDependencies()) {
            if (count($this->dependencies) === 1) {
                $message = new Message(
                    $translator->translate('This module requires the module "%s".'), // @translate
                    reset($this->dependencies)
                );
            } else {
                $message = new Message(
                    $translator->translate('This module requires modules "%s".'), // @translate
                    implode('", "', $this->dependencies)
                );
            }
            throw new ModuleCannotInstallException((string) $message);
        }
        if (!$this->checkAllResourcesToInstall()) {
            $message = new Message(
                $translator->translate('This module has resources that cannot be installed.') // @translate
            );
            throw new ModuleCannotInstallException((string) $message);
        }
        $sqlFile = $this->modulePath() . '/data/install/schema.sql';
        if (!$this->checkNewTablesFromFile($sqlFile)) {
            $message = new Message(
                $translator->translate('This module cannot install its tables, because they exist already. Try to remove them first.') // @translate
            );
            throw new ModuleCannotInstallException((string) $message);
        }
        $this->execSqlFromFile($sqlFile);
        $this
            ->installAllResources()
            ->manageConfig('install')
            ->manageMainSettings('install')
            ->manageSiteSettings('install')
            ->manageUserSettings('install')
            ->postInstall();
    }

    public function uninstall(ServiceLocatorInterface $services): void
    {
        $this->setServiceLocator($services);
        $this->preUninstall();
        $this->execSqlFromFile($this->modulePath() . '/data/install/uninstall.sql');
        $this
            // Don't uninstall user settings, they don't belong to admin.
            // ->manageUserSettings('uninstall')
            ->manageSiteSettings('uninstall')
            ->manageMainSettings('uninstall')
            ->manageConfig('uninstall')
            // ->uninstallAllResources()
            ->postUninstall();
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services): void
    {
        $filepath = $this->modulePath() . '/data/scripts/upgrade.php';
        if (file_exists($filepath) && filesize($filepath) && is_readable($filepath)) {
            // For compatibility with old upgrade files.
            $serviceLocator = $services;
            $this->setServiceLocator($serviceLocator);
            require_once $filepath;
        }
    }

    public function getInstallResources(): ?InstallResources
    {
        if (!class_exists(\Generic\InstallResources::class)) {
            // Use the module file first, since it must be present with the
            // right version, even if AbstractModule is older in another module.
            if (file_exists($filepath = OMEKA_PATH . '/modules/' . static::NAMESPACE . '/src/Generic/InstallResources.php')) {
                require_once $filepath;
            } elseif (file_exists($filepath = dirname(__DIR__, 3) . '/Generic/InstallResources.php')) {
                require_once $filepath;
            } elseif (file_exists($filepath = __DIR__ . '/InstallResources.php')) {
                require_once $filepath;
            } else {
                return null;
            }
        }
        $services = $this->getServiceLocator();
        return new \Generic\InstallResources($services);
    }

    public function checkAllResourcesToInstall(): bool
    {
        $installResources = $this->getInstallResources();
        return $installResources
            ? $installResources->checkAllResources(static::NAMESPACE)
            // Nothing to install.
            : true;
    }

    /**
     * @return self
     */
    public function installAllResources(): AbstractModule
    {
        $installResources = $this->getInstallResources();
        if (!$installResources) {
            // Nothing to install.
            return $this;
        }
        $installResources->createAllResources(static::NAMESPACE);
        return $this;
    }

    /**
     * @return self
     */
    public function uninstallAllResources(): AbstractModule
    {
        $installResources = $this->getInstallResources();
        if (
            !$installResources
            || !method_exists($installResources, 'deleteAllResources')
        ) {
            // Nothing to install.
            return $this;
        }
        $installResources->deleteAllResources(static::NAMESPACE);
        return $this;
    }

    public function checkAddonVersions(Event $event): void
    {
        global $globalCheckAddonVersions;

        if ($globalCheckAddonVersions) {
            return;
        }
        $globalCheckAddonVersions = true;

        $view = $event->getTarget();
        $hasGenericAsset = basename(dirname(__DIR__)) === 'modules' || file_exists(dirname(__DIR__, 3) . '/Generic/asset/js/check-versions.js');
        $asset = $hasGenericAsset
            ? $view->assetUrl('../../Generic/asset/js/check-versions.js', static::NAMESPACE)
            // Use a cdn to avoid issues with different versions in modules.
            // Of course, it's simpler to have an up-to-date Generic module.
            : 'https://cdn.jsdelivr.net/gh/Daniel-KM/Omeka-S-module-Generic@3.3.35/asset/js/check-versions.js';
        $view->headScript()
            ->appendFile($asset, 'text/javascript', ['defer' => 'defer']);
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();

        $formManager = $services->get('FormElementManager');
        $formClass = static::NAMESPACE . '\Form\ConfigForm';
        if (!$formManager->has($formClass)) {
            return '';
        }

        // Simplify config of modules.
        $renderer->ckEditor();

        $settings = $services->get('Omeka\Settings');

        $this->initDataToPopulate($settings, 'config');

        $data = $this->prepareDataToPopulate($settings, 'config');
        if (is_null($data)) {
            return '';
        }

        $form = $formManager->get($formClass);
        $form->init();
        $form->setData($data);
        $form->prepare();
        return $renderer->formCollection($form);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $config = $this->getConfig();
        $space = strtolower(static::NAMESPACE);
        if (empty($config[$space]['config'])) {
            return true;
        }

        $services = $this->getServiceLocator();
        $formManager = $services->get('FormElementManager');
        $formClass = static::NAMESPACE . '\Form\ConfigForm';
        if (!$formManager->has($formClass)) {
            return true;
        }

        $params = $controller->getRequest()->getPost();

        $form = $formManager->get($formClass);
        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $params = $form->getData();

        $settings = $services->get('Omeka\Settings');
        $defaultSettings = $config[$space]['config'];
        $params = array_intersect_key($params, $defaultSettings);
        foreach ($params as $name => $value) {
            $settings->set($name, $value);
        }
        return true;
    }

    public function handleMainSettings(Event $event): void
    {
        $this->handleAnySettings($event, 'settings');
    }

    public function handleSiteSettings(Event $event): void
    {
        $this->handleAnySettings($event, 'site_settings');
    }

    public function handleUserSettings(Event $event): void
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Mvc\Status $status */
        $status = $services->get('Omeka\Status');
        if ($status->isAdminRequest()) {
            /** @var \Laminas\Router\Http\RouteMatch $routeMatch */
            $routeMatch = $status->getRouteMatch();
            if (!in_array($routeMatch->getParam('controller'), ['Omeka\Controller\Admin\User', 'user'])) {
                return;
            }
            $this->handleAnySettings($event, 'user_settings');
        }
    }

    protected function modulePath(): string
    {
        return OMEKA_PATH . '/modules/' . static::NAMESPACE;
    }

    protected function preInstall(): void
    {
    }

    protected function postInstall(): void
    {
        $services = $this->getServiceLocator();
        $filepath = $this->modulePath() . '/data/scripts/install.php';
        if (file_exists($filepath) && filesize($filepath) && is_readable($filepath)) {
            $this->setServiceLocator($services);
            require_once $filepath;
        }
    }

    protected function preUninstall(): void
    {
    }

    protected function postUninstall(): void
    {
        $services = $this->getServiceLocator();
        $filepath = $this->modulePath() . '/data/scripts/uninstall.php';
        if (file_exists($filepath) && filesize($filepath) && is_readable($filepath)) {
            $this->setServiceLocator($services);
            require_once $filepath;
        }
    }

    /**
     * Check if new tables can be installed and remove empty existing tables.
     *
     * If a new table exists and is empty, it is removed, because it is probably
     * related to a broken installation.
     */
    protected function checkNewTablesFromFile(string $filepath): bool
    {
        if (!file_exists($filepath) || !filesize($filepath) || !is_readable($filepath)) {
            return true;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        // Get the list of all tables.
        $tables = $connection->executeQuery('SHOW TABLES;')->fetchFirstColumn();

        $dropTables = [];

        // Use single statements for execution.
        // See core commit #2689ce92f.
        $sql = file_get_contents($filepath);
        $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($sqls as $sql) {
            if (mb_strtoupper(mb_substr($sql, 0, 13)) !== 'CREATE TABLE ') {
                continue;
            }
            $table = trim(strtok(mb_substr($sql, 13), '('), "\"`' \n\r\t\v\0");
            if (!in_array($table, $tables)) {
                continue;
            }
            $result = $connection->executeQuery("SELECT * FROM `$table` LIMIT 1;")->fetchOne();
            if ($result !== false) {
                return false;
            }
            $dropTables[] = $table;
        }

        if (count($dropTables)) {
            // No check: if a table cannot be removed, an exception will be
            // thrown later.
            foreach ($dropTables as $table) {
                $connection->executeStatement("DROP TABLE `$table`;");
            }

            $translator = $services->get('MvcTranslator');
            $message = new \Omeka\Stdlib\Message(
                $translator->translate('The module removed tables "%s" from a previous broken install.'), // @translate
                implode('", "', $dropTables)
            );
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning($message);
        }

        return true;
    }

    /**
     * Execute a sql from a file.
     *
     * @param string $filepath
     * @return int|null
     */
    protected function execSqlFromFile(string $filepath): ?int
    {
        if (!file_exists($filepath) || !filesize($filepath) || !is_readable($filepath)) {
            return null;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');

        // Use single statements for execution.
        // See core commit #2689ce92f.
        $sql = file_get_contents($filepath);
        $sqls = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($sqls as $sql) {
            $result = $connection->executeStatement($sql);
        }

        return $result;
    }

    protected function getServiceSettings(string $settingsType): ?\Omeka\Settings\AbstractSettings
    {
        $settingsTypes = [
            // 'config' => 'Omeka\Settings',
            'settings' => 'Omeka\Settings',
            'site_settings' => 'Omeka\Settings\Site',
            'user_settings' => 'Omeka\Settings\User',
        ];
        if (!isset($settingsTypes[$settingsType])) {
            return null;
        }
        return $this->getServiceLocator()->get($settingsTypes[$settingsType]);
    }

    /**
     * Set, delete or update settings of the config of a module.
     *
     * @param string $process
     * @param array $values Values to use when process is update.
     * @return self
     */
    protected function manageConfig(string $process, array $values = []): AbstractModule
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        return $this->manageAnySettings($settings, 'config', $process, $values);
    }

    /**
     * Set, delete or update main settings.
     *
     * @param string $process
     * @param array $values Values to use when process is update.
     * @return self
     */
    protected function manageMainSettings(string $process, array $values = []): AbstractModule
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        return $this->manageAnySettings($settings, 'settings', $process, $values);
    }

    /**
     * Set, delete or update settings of all sites.
     *
     * @todo Replace by a single query (for install, uninstall, main, setting, user).
     *
     * @param string $process
     * @param array $values Values to use when process is update, by site id.
     * @return self
     */
    protected function manageSiteSettings(string $process, array $values = []): AbstractModule
    {
        $settingsType = 'site_settings';
        $config = $this->getConfig();
        $space = strtolower(static::NAMESPACE);
        if (empty($config[$space][$settingsType])) {
            return $this;
        }
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\Site');
        $api = $services->get('Omeka\ApiManager');
        $ids = $api->search('sites', [], ['returnScalar' => 'id'])->getContent();
        foreach ($ids as $id) {
            $settings->setTargetId($id);
            $this->manageAnySettings(
                $settings,
                $settingsType,
                $process,
                $values[$id] ?? []
            );
        }
        return $this;
    }

    /**
     * Set, delete or update settings of all users.
     *
     * @todo Replace by a single query (for install, uninstall, main, setting, user).
     *
     * @param string $process
     * @param array $values Values to use when process is update, by user id.
     * @return self
     */
    protected function manageUserSettings(string $process, array $values = []): AbstractModule
    {
        $settingsType = 'user_settings';
        $config = $this->getConfig();
        $space = strtolower(static::NAMESPACE);
        if (empty($config[$space][$settingsType])) {
            return $this;
        }
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\User');
        $api = $services->get('Omeka\ApiManager');
        $ids = $api->search('users', [], ['returnScalar' => 'id'])->getContent();
        foreach ($ids as $id) {
            $settings->setTargetId($id);
            $this->manageAnySettings(
                $settings,
                $settingsType,
                $process,
                $values[$id] ?? []
            );
        }
        return $this;
    }

    /**
     * Set, delete or update all settings of a specific type.
     *
     * It processes main settings, or one site, or one user.
     *
     * @param SettingsInterface $settings
     * @param string $settingsType
     * @param string $process "install", "uninstall", "update".
     * @param array $values
     * @return $this;
     */
    protected function manageAnySettings(SettingsInterface $settings, string $settingsType, string $process, array $values = []): AbstractModule
    {
        $config = $this->getConfig();
        $space = strtolower(static::NAMESPACE);
        if (empty($config[$space][$settingsType])) {
            return $this;
        }
        $defaultSettings = $config[$space][$settingsType];
        foreach ($defaultSettings as $name => $value) {
            switch ($process) {
                case 'install':
                    $settings->set($name, $value);
                    break;
                case 'uninstall':
                    $settings->delete($name);
                    break;
                case 'update':
                    if (array_key_exists($name, $values)) {
                        $settings->set($name, $values[$name]);
                    }
                    break;
            }
        }
        return $this;
    }

    /**
     * Prepare a settings fieldset.
     *
     * @param Event $event
     * @param string $settingsType
     * @return \Laminas\Form\Fieldset|null
     */
    protected function handleAnySettings(Event $event, string $settingsType): ?\Laminas\Form\Fieldset
    {
        global $globalNext;

        $services = $this->getServiceLocator();

        // TODO Check fieldsets in the config of the module.
        $settingFieldsets = [
            // 'config' => static::NAMESPACE . '\Form\ConfigForm',
            'settings' => static::NAMESPACE . '\Form\SettingsFieldset',
            'site_settings' => static::NAMESPACE . '\Form\SiteSettingsFieldset',
            'user_settings' => static::NAMESPACE . '\Form\UserSettingsFieldset',
        ];
        if (!isset($settingFieldsets[$settingsType])) {
            return null;
        }

        $settings = $this->getServiceSettings($settingsType);
        if (!$settings) {
            return null;
        }

        switch ($settingsType) {
            case 'settings':
                $id = null;
                break;
            case 'site_settings':
                $site = $services->get('ControllerPluginManager')->get('currentSite');
                $id = $site()->id();
                break;
            case 'user_settings':
                /** @var \Laminas\Router\Http\RouteMatch $routeMatch */
                $routeMatch = $services->get('Application')->getMvcEvent()->getRouteMatch();
                $id = $routeMatch->getParam('id');
                break;
        }

        // Simplify config of settings.
        if (empty($globalNext)) {
            $globalNext = true;
            $ckEditorHelper = $services->get('ViewHelperManager')->get('ckEditor');
            $ckEditorHelper();
        }

        // Allow to use a form without an id, for example to create a user.
        if ($settingsType !== 'settings' && !$id) {
            $data = [];
        } else {
            $this->initDataToPopulate($settings, $settingsType, $id);
            $data = $this->prepareDataToPopulate($settings, $settingsType);
            if (is_null($data)) {
                return null;
            }
        }

        $space = strtolower(static::NAMESPACE);

        /**
         * @var \Laminas\Form\Fieldset $fieldset
         * @var \Laminas\Form\Form $form
         */
        $fieldset = $services->get('FormElementManager')->get($settingFieldsets[$settingsType]);
        $fieldset->setName($space);
        $form = $event->getTarget();

        // In Omeka S v4, settings  are no more managed with fieldsets, but with
        // "element groups", to de-correlate setting storage and display.

        // Handle form loading.
        // There are default element groups:
        // - Settings:
        //   - general
        //   - security
        // - Site settings:
        //   - general
        //   - language
        //   - browse
        //   - show
        //   - search
        // - User settings: fieldsets "user-information"; "user-settings", "change-password"
        // and "edit-keys" are kept, but groups are added to fieldset "user-settings":
        //   - columns
        //   - browse_defaults
        // There are two possibilities to manage module features in settings:
        // - make each module a group
        // - or create new groups for each set of features: resource metadata,
        // site and pages params, viewers, contributions, public browse, public
        // resource, jobs to run…
        // The second way is more readable for admin, but in most of the cases,
        // features are very different, so there will be a group by module
        // anyway. Similar to module config, but config is not end-user friendly
        // (multiple pages).
        // So for now, let each module choose during upgrade to v4.
        // Nevertheless, to use group feature smartly, it is recommended to use
        // a generic list of groups similar to the site settings ones.
        // Maybe sub-groups may be interesting, but not possible for now.
        // In practice, there is a new option to set in each fieldset the group
        // where params are displayed.

        // TODO Order element groups.
        // TODO Move main params to site settings and user settings.

        $fieldsetElementGroups = $fieldset->getOption('element_groups');
        if ($fieldsetElementGroups) {
            $form->setOption('element_groups', array_merge($form->getOption('element_groups') ?: [], $fieldsetElementGroups));
        }

        // The user view is managed differently.
        if ($settingsType === 'user_settings') {
            // This process allows to save first level elements automatically.
            // @see \Omeka\Controller\Admin\UserController::editAction()
            $formFieldset = $form->get('user-settings');
            foreach ($fieldset->getFieldsets() as $subFieldset) {
                $formFieldset->add($subFieldset);
            }
            foreach ($fieldset->getElements() as $element) {
                $formFieldset->add($element);
            }
            $formFieldset->populateValues($data);
            $fieldset = $formFieldset;
        } else {
            // Allow to save data and to manage modules compatible with
            // Omeka S v3 and v4.
            //
            // In Omeka S v4, settings are no more de-nested, next to the new
            // "element group" feature, where default elements are attached
            // directly to the main form with a fake fieldset (not managed by
            // laminas), without using the formCollection() option.
            // So un-de-nested params are checked, but no more automatically
            // saved.
            // And when data is populated, it is not possible to determinate
            // directly if the form is valid or not as a whole, because the
            // check is done after the filling inside the controller.
            // To manage this new feature, either remove fieldsets and attach
            // elements directly to the form, either save elements via event
            // "view.browse.before", where the form is available.
            // This second way is simpler to manage modules compatible with
            // Omeka S v3 and v4, but it is not possible because there is a
            // redirect in the controller when post is successfull.
            // So append all elements and sub-fieldsets on the root of the form.
            if (version_compare(\Omeka\Module::VERSION, '4', '<')) {
                $form->add($fieldset);
                $form->get($space)->populateValues($data);
            } else {
                foreach ($fieldset->getFieldsets() as $subFieldset) {
                    $form->add($subFieldset);
                }
                foreach ($fieldset->getElements() as $element) {
                    $form->add($element);
                }
                $form->populateValues($data);
                $fieldset = $form;
            }
        }

        return $fieldset;
    }

    /**
     * Initialize each original settings, if not ready.
     *
     * If the default settings were never registered, it means an incomplete
     * config, install or upgrade, or a new site or a new user. In all cases,
     * check it and save default value first.
     *
     * @param SettingsInterface $settings
     * @param string $settingsType
     * @param int $id Site id or user id.
     * @param array $values Specific values to populate, e.g. translated strings.
     * @param bool True if processed.
     */
    protected function initDataToPopulate(SettingsInterface $settings, string $settingsType, $id = null, iterable $values = []): bool
    {
        // This method is not in the interface, but is set for config, site and
        // user settings.
        if (!method_exists($settings, 'getTableName')) {
            return false;
        }

        $config = $this->getConfig();
        $space = strtolower(static::NAMESPACE);
        if (empty($config[$space][$settingsType])) {
            return false;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->getServiceLocator()->get('Omeka\Connection');
        if ($id) {
            if (!method_exists($settings, 'getTargetIdColumnName')) {
                return false;
            }
            $sql = sprintf('SELECT id, value FROM %s WHERE %s = :target_id', $settings->getTableName(), $settings->getTargetIdColumnName());
            $stmt = $connection->executeQuery($sql, ['target_id' => $id]);
        } else {
            $sql = sprintf('SELECT id, value FROM %s', $settings->getTableName());
            $stmt = $connection->executeQuery($sql);
        }

        $currentSettings = $stmt->fetchAllKeyValue();
        $defaultSettings = $config[$space][$settingsType];
        // Skip settings that are arrays, because the fields "multi-checkbox"
        // and "multi-select" are removed when no value are selected, so it's
        // not possible to determine if it's a new setting or an old empty
        // setting currently. So fill them via upgrade in that case or fill the
        // values.
        // TODO Find a way to save empty multi-checkboxes and multi-selects (core fix).
        $defaultSettings = array_filter($defaultSettings, function ($v) {
            return !is_array($v);
        });
        $missingSettings = array_diff_key($defaultSettings, $currentSettings);

        foreach ($missingSettings as $name => $value) {
            $settings->set($name, array_key_exists($name, $values) ? $values[$name] : $value);
        }

        return true;
    }

    /**
     * Prepare data for a form or a fieldset.
     *
     * To be overridden by module for specific keys.
     *
     * @todo Use form methods to populate.
     *
     * @param SettingsInterface $settings
     * @param string $settingsType
     * @return array|null
     */
    protected function prepareDataToPopulate(SettingsInterface $settings, string $settingsType): ?array
    {
        $config = $this->getConfig();
        $space = strtolower(static::NAMESPACE);
        // Use isset() instead of empty() to give the possibility to display a
        // specific form.
        if (!isset($config[$space][$settingsType])) {
            return null;
        }

        $defaultSettings = $config[$space][$settingsType];

        $data = [];
        foreach ($defaultSettings as $name => $value) {
            $val = $settings->get($name, is_array($value) ? [] : null);
            $data[$name] = $val;
        }
        return $data;
    }

    /**
     * Check if the module has a dependency.
     *
     * This method is distinct of checkDependencies() for performance purpose.
     *
     * @return bool
     */
    protected function checkDependency(): bool
    {
        return empty($this->dependency)
            || $this->isModuleActive($this->dependency);
    }

    /**
     * Check if the module has dependencies.
     *
     * @return bool
     */
    protected function checkDependencies(): bool
    {
        return empty($this->dependencies)
            || $this->areModulesActive($this->dependencies);
    }

    /**
     * Check the version of a module and return a boolean or throw an exception.
     *
     * @throws \Omeka\Module\Exception\ModuleCannotInstallException
     */
    protected function checkModuleAvailability(string $moduleName, ?string $version = null, bool $required = false, bool $exception = false): bool
    {
        $services = $this->getServiceLocator();
        $module = $services->get('Omeka\ModuleManager')->getModule($moduleName);
        if (!$module || !$this->isModuleActive($moduleName)) {
            if (!$required) {
                return true;
            }
            if (!$exception) {
                return false;
            }
            // Else throw message below (required module with a version or not).
        } elseif (!$version || version_compare($module->getIni('version') ?? '', $version, '>=')) {
            return true;
        } elseif (!$exception) {
            return false;
        }
        $translator = $services->get('MvcTranslator');
        if ($version) {
            $message = new \Omeka\Stdlib\Message(
                $translator->translate('This module requires the module "%1$s", version %2$s or above.'), // @translate
                $moduleName,
                $version
            );
        } else {
            $message = new \Omeka\Stdlib\Message(
                $translator->translate('This module requires the module "%s".'), // @translate
                $moduleName
            );
        }
        throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
    }

    /**
     * Check the version of a module.
     *
     * It is recommended to use checkModuleAvailability(), that manages the fact
     * that the module may be required or not.
     */
    protected function isModuleVersionAtLeast(string $module, string $version): bool
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule($module);
        if (!$module) {
            return false;
        }

        $moduleVersion = $module->getIni('version');
        return $moduleVersion
            ? version_compare($moduleVersion, $version, '>=')
            : false;
    }

    /**
     * Check if a module is active.
     *
     * @param string $module
     * @return bool
     */
    protected function isModuleActive(string $module): bool
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule($module);
        return $module
            && $module->getState() === \Omeka\Module\Manager::STATE_ACTIVE;
    }

    /**
     * Check if a list of modules are active.
     *
     * @param array $modules
     * @return bool
     */
    protected function areModulesActive(array $modules): bool
    {
        $services = $this->getServiceLocator();
        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        foreach ($modules as $module) {
            $module = $moduleManager->getModule($module);
            if (!$module || $module->getState() !== \Omeka\Module\Manager::STATE_ACTIVE) {
                return false;
            }
        }
        return true;
    }

    /**
     * Disable a module.
     *
     * @param string $module
     * @return bool
     */
    protected function disableModule(string $module): bool
    {
        // Check if the module is enabled first to avoid an exception.
        if (!$this->isModuleActive($module)) {
            return true;
        }

        // Check if the user is a global admin to avoid right issues.
        $services = $this->getServiceLocator();
        $user = $services->get('Omeka\AuthenticationService')->getIdentity();
        if (!$user || $user->getRole() !== \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN) {
            return false;
        }

        /** @var \Omeka\Module\Manager $moduleManager */
        $moduleManager = $services->get('Omeka\ModuleManager');
        $managedModule = $moduleManager->getModule($module);
        $moduleManager->deactivate($managedModule);

        $translator = $services->get('MvcTranslator');
        $message = new \Omeka\Stdlib\Message(
            $translator->translate('The module "%s" was automatically deactivated because the dependencies are unavailable.'), // @translate
            $module
        );
        $messenger = $services->get('ControllerPluginManager')->get('messenger');
        $messenger->addWarning($message);

        $logger = $services->get('Omeka\Logger');
        $logger->warn($message);
        return true;
    }

    /**
     * Get each line of a string separately.
     *
     * @deprecated Since 3.3.22. Use \Omeka\Form\Element\ArrayTextarea.
     * @param string $string
     * @return array
     */
    public function stringToList($string): array
    {
        return array_filter(array_map('trim', explode("\n", $this->fixEndOfLine($string))), 'strlen');
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     *
     * @deprecated Since 3.3.22. Use \Omeka\Form\Element\ArrayTextarea.
     * @param string $string
     * @return string
     */
    protected function fixEndOfLine($string): string
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $string);
    }
}
