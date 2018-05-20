<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FillFileSize implements ConstructedMigrationInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    private $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    public function up(Connection $conn)
    {
        $basePath = $this->services->get('Config')['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');
        /** @var \Omeka\Mvc\Controller\Plugin\Logger $logger */
        $logger = $this->services->get('Omeka\Logger');
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->services->get('Omeka\EntityManager');
        $mediaRepository = $entityManager->getRepository(\Omeka\Entity\Media::class);

        // Get all media files without size in one array (not a heavy one).
        $stmt = $conn->query('SELECT id FROM media WHERE renderer = "file" AND size IS NULL');
        $mediaIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Update filesize.
        $stmt = $conn->prepare('UPDATE media SET size = ? WHERE id = ?');
        foreach ($mediaIds as $id) {
            /** @var \Omeka\Entity\Media $media */
            $media = $mediaRepository->find($id);
            $filepath = $basePath . '/original/' . $media->getFilename();
            if (!file_exists($filepath)) {
                $logger->err(sprintf('Media #%d: File can’t be found: "%s"', $id, $media->getFilename()));
                continue;
            }
            $filesize = filesize($filepath);
            $stmt->bindValue(1, $filesize);
            $stmt->bindValue(2, $id);
            $stmt->execute();
        }
    }

    public static function create(ServiceLocatorInterface $serviceLocator)
    {
        return new self($serviceLocator);
    }
}
