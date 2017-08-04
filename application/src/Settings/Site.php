<?php
namespace Omeka\Settings;

class Site extends AbstractTableSettings
{
    public function getTableName()
    {
        return 'site_setting';
    }

    public function getIdColumnName()
    {
        return 'site_id';
    }
}
