<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;

class AddMediaRender implements ConstructedMigrationInterface
{
    private $em;

    public static function create(ServiceLocatorInterface $services)
    {
        return new self($services->get('Omeka\EntityManager'));
    }

    public function __construct($em)
    {
        $this->em = $em;
    }

    public function up(Connection $conn)
    {
        // Add the mediaRender resource page block layout to media pages that
        // only have the values block layout.
        $query = $this->em->createQuery('SELECT s FROM Omeka\Entity\SiteSetting s WHERE s.id LIKE :id');
        $query->setParameter('id', 'theme_settings_%');
        $siteSettings = $query->getResult();
        foreach ($siteSettings as $siteSetting) {
            $value = $siteSetting->getValue();
            if (isset($value['resource_page_blocks']['media']['main'])
                && ['values'] === $value['resource_page_blocks']['media']['main']
            ) {
                $value['resource_page_blocks']['media']['main'] = ['mediaRender', 'values'];
                $siteSetting->setValue($value);
            }
        }
        $this->em->flush();
    }
}
