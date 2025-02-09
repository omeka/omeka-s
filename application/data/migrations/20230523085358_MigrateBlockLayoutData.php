<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;

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
            $layoutData = $block->getLayoutData();
            if (isset($data['divclass'])) {
                $layoutData['class'] = $data['divclass'];
                unset($data['divclass']);
                $block->setData($data);
                $block->setLayoutData($layoutData);
            }
        }
        // Migrate "Media embed" (media) blocks.
        foreach ($blocksRepository->findBy(['layout' => 'media']) as $block) {
            $data = $block->getData();
            $layoutData = $block->getLayoutData();
            if (isset($data['alignment'])) {
                $layoutData['alignment_block'] = $data['alignment'];
                if ('center' === $data['alignment']) {
                    $layoutData['alignment_text'] = 'center';
                }
                unset($data['alignment']);
                $block->setData($data);
                $block->setLayoutData($layoutData);
            }
        }
        // Migrate "Asset" (asset) blocks.
        foreach ($blocksRepository->findBy(['layout' => 'asset']) as $block) {
            $data = $block->getData();
            $layoutData = $block->getLayoutData();
            if (isset($data['className'])) {
                $layoutData['class'] = $data['className'];
                unset($data['className']);
                $block->setData($data);
                $block->setLayoutData($layoutData);
            }
            if (isset($data['alignment'])) {
                $layoutData['alignment_block'] = $data['alignment'];
                if ('center' === $data['alignment']) {
                    $layoutData['alignment_text'] = 'center';
                }
                unset($data['alignment']);
                $block->setData($data);
                $block->setLayoutData($layoutData);
            }
        }
        $this->em->flush();
    }
}
