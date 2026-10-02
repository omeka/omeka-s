<?php
namespace Omeka\View\Helper;

use Laminas\Form\Form;
use Laminas\Form\FormElementManager;
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
    protected FormElementManager $formElementManager;

    public function __construct(OptionSidebarService $optionSidebar, FormElementManager $formElementManager)
    {
        $this->optionSidebar = $optionSidebar;
        $this->formElementManager = $formElementManager;
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
        $view->headScript()->appendFile($view->assetUrl('vendor/sortablejs/Sortable.min.js', 'Omeka'));
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

        $sharedLevel = $this->optionSidebar->getSharedLevel($key);
        $canSaveShared = $sharedLevel && $this->optionSidebar->canSave($key, $sharedLevel, $site);

        $csrfForm = $this->formElementManager->get(Form::class, ['name' => 'option_sidebar']);

        return $view->partial(self::PARTIAL_NAME, [
            'key' => $key,
            'filterLabel' => $this->optionSidebar->getFilterLabel($key),
            'site' => $site,
            'groups' => $groups,
            'pinned' => $pinned,
            'arrangement' => $arrangement,
            'arrangements' => $this->optionSidebar->getArrangementsByScope($key, $site ? $site->id() : null, $canSaveShared),
            'sharedLevel' => $sharedLevel,
            'canSaveShared' => $canSaveShared,
            'saveUrl' => $view->url('admin/default', ['controller' => 'option-sidebar', 'action' => 'save']),
            'csrf' => $csrfForm->get('option_sidebar_csrf')->getValue(),
        ]);
    }
}
