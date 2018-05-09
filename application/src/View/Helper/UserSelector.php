<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering the user selector.
 */
class UserSelector extends AbstractHelper
{
    /**
     * Return the user selector form control.
     *
     * @param string $title The title of the selector
     * @param bool $alwaysOpen Whether the selector is always open
     * @return string
     */
    public function __invoke($title = null, $alwaysOpen = true)
    {
        $users = $this->getView()->api()->search('users', ['sort_by' => 'name'])->getContent();

        $usersByInitial = [];
        foreach ($users as $user) {
            $initial = strtoupper($user->name())[0];
            $usersByInitial[$initial][] = $user;
        }

        return $this->getView()->partial(
            'common/user-selector',
            [
                'users' => $users,
                'usersByInitial' => $usersByInitial,
                'title' => $title,
                'alwaysOpen' => $alwaysOpen,
            ]
        );
    }
}
