<?php
namespace Omeka\Stdlib;

use Collator;
use InvalidArgumentException;
use Laminas\I18n\Translator\TranslatorInterface;
use Omeka\Module\Manager as ModuleManager;
use Omeka\Module\Module;
use Omeka\Settings\FallbackSettings;

/**
 * Organize the options of "add" sidebars, such as "Add new block" and "Add
 * media".
 *
 * Each sidebar is registered under the "option_sidebars" config key, which
 * names the plugin manager that supplies its options, the setting levels, in
 * fallback order, where its arrangement may be saved, and its default pinned
 * options. The sidebar key is also the plugin manager's own config key, where
 * the optional "categories" and "category_names" keys describe its options.
 */
class OptionSidebar
{
    protected array $config;
    protected array $managers;
    protected ModuleManager $moduleManager;
    protected FallbackSettings $fallbackSettings;
    protected TranslatorInterface $translator;
    protected $collator;

    /**
     * @param array $config The application config
     * @param array $managers Plugin managers keyed by sidebar key
     * @param ModuleManager $moduleManager
     * @param FallbackSettings $fallbackSettings
     * @param TranslatorInterface $translator
     */
    public function __construct(
        array $config,
        array $managers,
        ModuleManager $moduleManager,
        FallbackSettings $fallbackSettings,
        TranslatorInterface $translator
    ) {
        $this->config = $config;
        $this->managers = $managers;
        $this->moduleManager = $moduleManager;
        $this->fallbackSettings = $fallbackSettings;
        $this->translator = $translator;
    }

    /**
     * Is a sidebar registered under this key?
     */
    public function has(string $key): bool
    {
        return isset($this->config['option_sidebars'][$key], $this->managers[$key]);
    }

    /**
     * Get the setting levels of a sidebar, in fallback order.
     */
    public function getLevels(string $key): array
    {
        return $this->getSpec($key)['levels'] ?? [];
    }

    /**
     * Get the ID of the setting that stores a sidebar's arrangement.
     */
    public function getSettingId(string $key): string
    {
        return sprintf('option_sidebar_%s', $key);
    }

    /**
     * Get the names of a sidebar's options, minus excluded names.
     */
    public function getNames(string $key): array
    {
        $exclude = $this->getSpec($key)['exclude'] ?? [];
        return array_values(array_diff($this->managers[$key]->getRegisteredNames(), $exclude));
    }

    /**
     * Get a sidebar's options, grouped and sorted for display.
     *
     * Options go into their declared category, otherwise into Core (options
     * from Omeka itself), a group named after the module that provides them,
     * or Other. Groups are ordered declared categories first (by position,
     * then label), then Core, then module groups (by module name), then Other.
     * Options within a group are ordered by label.
     *
     * @return array A list of groups, each with "key", "label", and "options";
     *   each option has "name", "label", and "module" (a module name or null)
     */
    public function getGroups(string $key): array
    {
        $names = $this->getNames($key);
        $manager = $this->managers[$key];
        $categories = $this->config[$key]['categories'] ?? [];
        $categoryNames = $this->config[$key]['category_names'] ?? [];

        $groups = [];
        foreach ($names as $name) {
            $service = $manager->get($name);
            $class = get_class($service);
            $module = $this->getModuleForClass($class);
            $category = $categoryNames[$name] ?? null;
            if (is_string($category) && isset($categories[$category])) {
                $group = [
                    'key' => sprintf('category:%s', $category),
                    'label' => $this->translator->translate($categories[$category]['label'] ?? $category),
                    'rank' => 0,
                    'position' => (int) ($categories[$category]['position'] ?? 0),
                ];
            } elseif (0 === strpos($class, 'Omeka\\')) {
                $group = [
                    'key' => 'core',
                    'label' => $this->translator->translate('Core'),
                    'rank' => 1,
                    'position' => 0,
                ];
            } elseif ($module) {
                $group = [
                    'key' => sprintf('module:%s', $module->getId()),
                    'label' => $module->getName(),
                    'rank' => 2,
                    'position' => 0,
                ];
            } else {
                $group = [
                    'key' => 'other',
                    'label' => $this->translator->translate('Other'),
                    'rank' => 3,
                    'position' => 0,
                ];
            }
            if (!isset($groups[$group['key']])) {
                $groups[$group['key']] = $group + ['options' => []];
            }
            $groups[$group['key']]['options'][] = [
                'name' => $name,
                'label' => $this->translator->translate($service->getLabel()),
                'module' => $module ? $module->getName() : null,
            ];
        }

        usort($groups, function ($a, $b) {
            return [$a['rank'], $a['position']] <=> [$b['rank'], $b['position']]
                ?: $this->compare($a['label'], $b['label']);
        });
        foreach ($groups as &$group) {
            usort($group['options'], function ($a, $b) {
                return $this->compare($a['label'], $b['label']);
            });
            unset($group['rank'], $group['position']);
        }
        unset($group);
        return $groups;
    }

    /**
     * Get a sidebar's arrangement: its pinned and hidden option names.
     *
     * The levels are checked in fallback order, and the first one with a
     * well-formed value wins. With no such value, the arrangement is the
     * config default.
     *
     * @param string $key
     * @param int|null $siteId The site to read site-level settings for
     * @return array With "pinned", "hidden", and "source" (the level that
     *   supplied the arrangement, or "default")
     */
    public function getArrangement(string $key, ?int $siteId = null): array
    {
        $settingId = $this->getSettingId($key);
        $targetIds = $siteId ? ['site' => $siteId] : [];
        foreach ($this->getLevels($key) as $level) {
            $result = $this->fallbackSettings->getWithSource($settingId, [$level], null, $targetIds);
            if (null === $result['source'] || !$this->isArrangement($result['value'])) {
                continue;
            }
            $arrangement = $this->normalize(
                $key,
                $result['value']['pinned'] ?? [],
                $result['value']['hidden'] ?? []
            );
            $arrangement['source'] = $result['source'];
            return $arrangement;
        }
        return $this->getDefaultArrangement($key);
    }

    /**
     * Normalize pinned and hidden option names.
     *
     * Keeps only the names of registered options, removes duplicates, and
     * removes hidden names from the pinned names.
     *
     * @param string $key
     * @param mixed $pinned
     * @param mixed $hidden
     * @return array With "pinned" and "hidden"
     */
    public function normalize(string $key, $pinned, $hidden): array
    {
        $names = $this->getNames($key);
        $filter = function ($list) use ($names) {
            if (!is_array($list)) {
                return [];
            }
            return array_values(array_unique(array_filter($list, function ($name) use ($names) {
                return is_string($name) && in_array($name, $names, true);
            })));
        };
        $hidden = $filter($hidden);
        $pinned = array_values(array_diff($filter($pinned), $hidden));
        return ['pinned' => $pinned, 'hidden' => $hidden];
    }

    /**
     * Get a sidebar's default arrangement from config.
     *
     * The sidebar's "pinned" config key maps option names to positions. A false
     * or null position unpins the option, which lets a later config file unpin
     * an option pinned by an earlier one.
     */
    public function getDefaultArrangement(string $key): array
    {
        $positions = array_filter($this->getSpec($key)['pinned'] ?? [], function ($position) {
            return false !== $position && null !== $position;
        });
        asort($positions, SORT_NUMERIC);
        $arrangement = $this->normalize($key, array_keys($positions), []);
        $arrangement['source'] = 'default';
        return $arrangement;
    }

    /**
     * Get the active module whose ID is the root namespace of a class.
     *
     * Only a module's Module class is required to live in the namespace named
     * after the module. Other module classes follow the convention without
     * being required to, so this is a best guess.
     */
    protected function getModuleForClass(string $class): ?Module
    {
        $position = strpos($class, '\\');
        if (false === $position) {
            return null;
        }
        $module = $this->moduleManager->getModule(substr($class, 0, $position));
        if ($module && ModuleManager::STATE_ACTIVE === $module->getState()) {
            return $module;
        }
        return null;
    }

    /**
     * Get the config of a registered sidebar.
     *
     * @throws InvalidArgumentException When no sidebar has this key
     */
    protected function getSpec(string $key): array
    {
        if (!$this->has($key)) {
            throw new InvalidArgumentException(sprintf('No option sidebar is registered as "%s".', $key));
        }
        return $this->config['option_sidebars'][$key];
    }

    /**
     * Is a stored value a well-formed arrangement?
     */
    protected function isArrangement($value): bool
    {
        return is_array($value)
            && (!isset($value['pinned']) || is_array($value['pinned']))
            && (!isset($value['hidden']) || is_array($value['hidden']));
    }

    /**
     * Compare two labels, using the locale-aware collator when available.
     *
     * This matches how AbstractPluginManager::getRegisteredNames() sorts.
     */
    protected function compare(string $a, string $b): int
    {
        if (!extension_loaded('intl')) {
            return strnatcasecmp($a, $b);
        }
        if (!isset($this->collator)) {
            $this->collator = new Collator('root');
        }
        return $this->collator->compare($a, $b);
    }
}
