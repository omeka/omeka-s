<?php declare(strict_types=1);

namespace Omeka\Service\Stdlib;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Stdlib\Cipher;
use Omeka\Stdlib\SecretKey;
use Psr\Container\ContainerInterface;

class CipherFactory implements FactoryInterface
{
    /**
     * Create the Cipher service from the resolved secret key.
     *
     * @return Cipher
     */
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new Cipher(SecretKey::resolve());
    }
}
