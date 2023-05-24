<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Db\Migration\MigrationInterface;

class MigrateBlockLayoutData implements ConstructedMigrationInterface
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
        $blocksRepository = $this->em->getRepository('Omeka\Entity\SitePageBlock');

        // Migrate "HTML" (html) blocks.
        foreach ($blocksRepository->findBy(['layout' => 'html']) as $block) {
            $data = $block->getData();
            $layoutData = [];
            if (isset($data['divclass'])) {
                $layoutData['class'] = $data['divclass'];
                unset($data['divclass']);
                $block->setData($data);
                $block->setLayoutData($layoutData);
            }
        }
        // Migrate "Media embed" (html) blocks.
        foreach ($blocksRepository->findBy(['layout' => 'media']) as $block) {
            $data = $block->getData();
            $layoutData = [];
            if (isset($data['alignment'])) {
                $layoutData['alignment'] = $data['alignment'];
                unset($data['alignment']);
                $block->setData($data);
                $block->setLayoutData($layoutData);
            }
        }
        $this->em->flush();
    }
}
