<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Stdlib\OptionSidebar as OptionSidebarService;

/**
 * View helper for rendering the options of an "add" sidebar.
 */
class OptionSidebar extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/option-sidebar';

    protected OptionSidebarService $optionSidebar;

    public function __construct(OptionSidebarService $optionSidebar)
    {
        $this->optionSidebar = $optionSidebar;
    }

    /**
     * Render the options of an "add" sidebar.
     *
     * Each option is a button.option with the option name as its value. The
     * page that renders the sidebar handles what clicking an option does.
     *
     * @param string $key The sidebar key, as registered under option_sidebars
     * @param SiteRepresentation|null $site The site, for sidebars that can be
     *   arranged per site
     */
    public function __invoke(string $key, ?SiteRepresentation $site = null): string
    {
        $view = $this->getView();
        $view->headScript()->appendFile($view->assetUrl('js/option-sidebar.js', 'Omeka'));

        $groups = $this->optionSidebar->getGroups($key);
        $arrangement = $this->optionSidebar->getArrangement($key, $site ? $site->id() : null);

        $options = [];
        foreach ($groups as $group) {
            foreach ($group['options'] as $option) {
                $options[$option['name']] = $option;
            }
        }
        $pinned = [];
        foreach ($arrangement['pinned'] as $name) {
            $pinned[] = $options[$name];
        }

        return $view->partial(self::PARTIAL_NAME, [
            'key' => $key,
            'groups' => $groups,
            'pinned' => $pinned,
            'hidden' => $arrangement['hidden'],
            'visibleCount' => count(array_diff(array_keys($options), $arrangement['hidden'])),
        ]);
    }
}
