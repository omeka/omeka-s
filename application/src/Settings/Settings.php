<?php
namespace Omeka\Settings;

class Settings extends AbstractSettings
{
    public function getTableName()
    {
        return 'setting';
    }
}
