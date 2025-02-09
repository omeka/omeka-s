<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;

class ConvertItemShowcaseToMedia implements ConstructedMigrationInterface
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
        // Convert item showcase blocks to media embed blocks.
        $blocksRepository = $this->em->getRepository('Omeka\Entity\SitePageBlock');
        foreach ($blocksRepository->findBy(['layout' => 'itemShowCase']) as $block) {
            $data = $block->getData();
            $data['layout'] = 'horizontal';
            $data['media_display'] = 'thumbnail';
            $block->setData($data);
            $block->setLayout('media');
        }
        $this->em->flush();
    }
}
