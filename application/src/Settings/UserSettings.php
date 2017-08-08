<?php
namespace Omeka\Settings;

class UserSettings extends AbstractTargetSettings
{
    public function getTableName()
    {
        return 'user_setting';
    }

    public function getTargetIdColumnName()
    {
        return 'user_id';
    }
}
