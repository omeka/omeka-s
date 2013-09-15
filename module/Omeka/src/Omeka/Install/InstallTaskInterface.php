<?php
namespace Omeka\Install;

use Doctrine\ORM\EntityManager;

interface InstallTaskInterface
{
    public function perform();
}