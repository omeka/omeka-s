<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

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
        $usersByInitial = $this->groupUsersByInitial($users);

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

    /**
     * Group users by the uppercase initial of their display name.
     *
     * Uses multibyte-safe string functions to support non-ASCII names.
     *
     * @param iterable $users.
     * @return array
     */
    protected function groupUsersByInitial(iterable $users): array
    {
        $usersByInitial = [];
        foreach ($users as $user) {
            $initial = mb_substr($user->name(), 0, 1, 'UTF-8');
            $upper_initial = mb_strtoupper($initial, 'UTF-8');
            $usersByInitial[$upper_initial][] = $user;
        }
        return $usersByInitial;
    }
}
