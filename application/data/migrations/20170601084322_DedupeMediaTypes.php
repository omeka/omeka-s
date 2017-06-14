<?php
namespace Omeka\Db\Migrations;

use Doctrine\DBAL\Connection;
use Omeka\Db\Migration\MigrationInterface;

class DedupeMediaTypes implements MigrationInterface
{
    const MEDIA_TYPE_ALIASES = [
        // application/ogg
        'application/x-ogg' => 'application/ogg',
        // application/rtf
        'text/rtf' => 'application/rtf',
        // audio/midi
        'audio/mid' => 'audio/midi',
        'audio/x-midi' => 'audio/midi',
        // audio/mpeg
        'audio/mp3' => 'audio/mpeg',
        'audio/mpeg3' => 'audio/mpeg',
        'audio/x-mp3' => 'audio/mpeg',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-mpeg3' => 'audio/mpeg',
        'audio/x-mpegaudio' => 'audio/mpeg',
        'audio/x-mpg' => 'audio/mpeg',
        // audio/ogg
        'audio/x-ogg' => 'audio/ogg',
        // audio/x-aac
        'audio/aac' => 'audio/x-aac',
        // audio/x-aiff
        'audio/aiff' => 'audio/x-aiff',
        // audio/x-ms-wma
        'audio/x-wma' => 'audio/x-ms-wma',
        'audio/wma' => 'audio/x-ms-wma',
        // audio/mp4
        'audio/x-mp4' => 'audio/mp4',
        'audio/x-m4a' => 'audio/mp4',
        // audio/x-wav
        'audio/wav' => 'audio/x-wav',
        // image/bmp
        'image/x-ms-bmp' => 'image/bmp',
        // image/x-icon
        'image/icon' => 'image/x-icon',
        // video/mp4
        'video/x-m4v' => 'video/mp4',
        // video/x-ms-asf
        'video/asf' => 'video/x-ms-asf',
        // video/x-ms-wmv
        'video/wmv' => 'video/x-ms-wmv',
        // video/x-msvideo
        'video/avi' => 'video/x-msvideo',
        'video/msvideo' => 'video/x-msvideo',
    ];

    public function up(Connection $conn)
    {
        $stmt = $conn->prepare('UPDATE media SET media_type = ? WHERE media_type = ?');
        foreach (self::MEDIA_TYPE_ALIASES as $nonstandard => $standard) {
            $stmt->bindValue(1, $standard);
            $stmt->bindValue(2, $nonstandard);
            $stmt->execute();
        }
    }
}
