<?php
namespace Omeka\Settings;

class SiteSettings extends AbstractSettings
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
