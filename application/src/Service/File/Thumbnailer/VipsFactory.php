<?php declare(strict_types=1);

namespace Omeka\Service\File\Thumbnailer;

use Omeka\File\Thumbnailer\Vips;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class VipsFactory implements FactoryInterface
{
    /**
     * Create the Vips thumbnailer service.
     *
     * @return Vips
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $cli = $services->get('Omeka\Cli');

        $vips = new Vips(
            $cli,
            $services->get('Omeka\File\TempFileFactory')
        );

        // Set path one time. Don't check if the path is false.
        $vipsDir = $services->get('Config')['thumbnails']['thumbnailer_options']['vips_dir'];
        try {
            $vips->setVipsPath($vipsDir);
        } catch (\Omeka\File\Exception\InvalidThumbnailerException $e) {
        }

        return $vips;
    }
}
