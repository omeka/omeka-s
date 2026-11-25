<?php declare(strict_types=1);

namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\File\Exception\InvalidThumbnailerException;
use Omeka\File\Thumbnailer;

class AutoFactory implements FactoryInterface
{
    /**
     * Create the Auto thumbnailer service.
     *
     * @return Thumbnailer\Auto
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // List and sort the available thumbnailers.
        // Ideally, the list of media-types can be prepared one time, but the
        // thumbnailers don't provide a standardized list of standard names, and
        // servers doesn't give the same media-types in all cases, and vips can
        // use "convert" of ImageMagick for unmanaged formats, or ffmpeg.
        // So it's simpler to check each file dynamically, except for gd.
        $availables = [];

        /** @var \Omeka\File\ThumbnailManager $thumbnailManager */
        $thumbnailManager = $services->get(\Omeka\File\ThumbnailManager::class);
        $thumbnailerOptions = $thumbnailManager->getThumbnailerOptions();

        // TODO Check vipsScaler extension for jpeg and png (see https://www.mediawiki.org/wiki/Extension:VipsScaler).
        // TODO Currently, the thumbnail manager doesn't cache the thumbnailers (buildThumbnailer())..

        try {
            $thumbnailer = $services->get(Thumbnailer\Vips::class);
            $thumbnailer->setOptions($thumbnailerOptions);
            $availables[Thumbnailer\Vips::class] = [
                'thumbnailer' => $thumbnailer,
                'supported' => [],
                'unsupported' => [],
                'dynamic' => true,
            ];
        } catch (InvalidThumbnailerException $e) {
        }

        try {
            $thumbnailer = $services->get(Thumbnailer\ImageMagick::class);
            $thumbnailer->setOptions($thumbnailerOptions);
            $availables[Thumbnailer\ImageMagick::class] = [
                'thumbnailer' => $thumbnailer,
                'supported' => [],
                'unsupported' => [],
                'dynamic' => true,
            ];
        } catch (InvalidThumbnailerException $e) {
        }

        if (extension_loaded('imagick')) {
            // \Imagick::queryFormats() outputs a long list of formats, but not
            // media types.
            $thumbnailer = $services->get(Thumbnailer\Imagick::class);
            $thumbnailer->setOptions($thumbnailerOptions);
            $availables[Thumbnailer\Imagick::class] = [
                'thumbnailer' => $thumbnailer,
                'supported' => [],
                'unsupported' => [],
                'dynamic' => true,
            ];
        }

        if (extension_loaded('gd')) {
            $thumbnailer = $services->get(Thumbnailer\Gd::class);
            $thumbnailer->setOptions($thumbnailerOptions);
            $managed = [
                'GIF Create Support' => 'image/gif',
                'JPEG Support' => 'image/jpeg',
                'PNG Support' => 'image/png',
                'WBMP Support' => 'image/wbmp',
                'XPM Support' => 'image/xpm',
                'XBM Support' => 'image/xbm',
                'WebP Support' => 'image/webp',
                'BMP Support' => 'image/bmp',
            ];
            $mediaTypes = array_values(array_intersect_key($managed, array_filter(gd_info())));
            if (in_array('image/xpm', $mediaTypes)) {
                $mediaTypes[] = 'image/x-xpixmap';
            }
            $availables[Thumbnailer\Gd::class] = [
                'thumbnailer' => $thumbnailer,
                'supported' => $mediaTypes,
                'unsupported' => [],
                'dynamic' => false,
            ];
        }

        return new Thumbnailer\Auto(
            $services->get('Omeka\File\TempFileFactory'),
            $availables
        );
    }
}
