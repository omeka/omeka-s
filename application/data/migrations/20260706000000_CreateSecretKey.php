<?php declare(strict_types=1);

namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;
use Omeka\Stdlib\SecretKey;

class CreateSecretKey implements ConstructedMigrationInterface
{
    /**
     * @var \Laminas\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Messenger
     */
    private $messenger;

    public static function create(ServiceLocatorInterface $services)
    {
        return new self(
            $services->get('Omeka\Logger'),
            $services->get('ControllerPluginManager')->get('messenger')
        );
    }

    public function __construct($logger, $messenger)
    {
        $this->logger = $logger;
        $this->messenger = $messenger;
    }

    public function up(Connection $conn)
    {
        if (SecretKey::resolve() !== null) {
            return;
        }

        if (SecretKey::store(SecretKey::generate())) {
            $this->logger->info('A secret key was generated in config/secret_key.php to encrypt secrets.'); // @translate
            return;
        }

        $message = 'The secret key could not be created: set it manually in config/secret_key.php or set the environment variable "OMEKA_SECRET_KEY" to encrypt secrets (module api keys, etc.).'; // @translate
        $this->logger->warn($message);
        $this->messenger->addWarning($message);
    }
}
