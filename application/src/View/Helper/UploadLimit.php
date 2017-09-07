<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering the upload size limit.
 */
class UploadLimit extends AbstractHelper
{
    const MEGABYTE_BYTES = 1048576;

    /**
     * Show the upload size limit.
     *
     * @return string
     */
    public function __invoke()
    {
        $uploadMaxFilesize = $this->parseSize(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));

        $uploadLimit = min($uploadMaxFilesize, $postMaxSize);
        $uploadLimit = round($uploadLimit / self::MEGABYTE_BYTES);

        $format = $this->getView()->translate('The maximum upload size is %s MB.');
        return sprintf($format, $uploadLimit);
    }

    /**
     * Get the size in bytes represented by the given php ini config string
     *
     * @param string $sizeString
     * @return int Size in bytes
     */
    protected function parseSize($sizeString)
    {
        $value = intval($sizeString);

        $lastChar = substr($sizeString, -1);
        // Note: these cases fall through purposely
        switch ($lastChar) {
            case 'g':
            case 'G':
                $value *= 1024;
            case 'm':
            case 'M':
                $value *= 1024;
            case 'k':
            case 'K':
                $value *= 1024;
        }

        return $value;
    }
}
