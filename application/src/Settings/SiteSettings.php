<?php
namespace Omeka\Settings;

class SiteSettings extends AbstractTargetSettings
{
    public function getTableName()
    {
        return 'site_setting';
    }

    public function getTargetIdColumnName()
    {
        return 'site_id';
    }
}
