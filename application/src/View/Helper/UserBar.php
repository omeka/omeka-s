<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering the user bar.
 */
class UserBar extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/user-bar';

    /**
     * Render the user bar.
     *
     * @param string|null $partialName Name of view script, or a view model
     * @return string
     */
    public function __invoke($partialName = null)
    {
        $view = $this->getView();
        $showUserBar = $view->siteSetting('show_user_bar', 0);
        if ($showUserBar == -1) {
            return '';
        }

        $user = $view->identity();
        if ($showUserBar != 1 && !$user) {
            return '';
        }

        $site = $view->vars()->site;
        if (empty($site)) {
            return '';
        }

        $partialName = $partialName ?: self::PARTIAL_NAME;

        return $view->partial(
            $partialName,
            [
                'site' => $site,
                'user' => $user,
            ]
        );
    }
}
