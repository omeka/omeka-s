<?php
namespace Omeka\Db\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class IpAddress extends Type
{
    const IP_ADDRESS = 'ip_address';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'VARBINARY(16)';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return inet_ntop($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return inet_pton($value);
    }

    public function getName()
    {
        return self::IP_ADDRESS;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
