<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Db\Migration\ConstructedMigrationInterface;

class AddCaptchaSetting implements ConstructedMigrationInterface
{
    private $settings;

    public static function create(ServiceLocatorInterface $services)
    {
        return new self($services->get('Omeka\Settings'));
    }

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function up(Connection $conn)
    {
        // Select reCAPTCHA v2 as the CAPTCHA provider if it's configured. Don't
        // replace a provider that's already been selected.
        if (null !== $this->settings->get('captcha')) {
            return;
        }
        $siteKey = trim((string) $this->settings->get('recaptcha_site_key'));
        $secretKey = trim((string) $this->settings->get('recaptcha_secret_key'));
        if ('' !== $siteKey && '' !== $secretKey) {
            $this->settings->set('captcha', 'recaptcha_v2');
        }
    }
}
