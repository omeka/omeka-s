<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Uploader;
use Omeka\Stdlib\ErrorStore;
use Laminas\View\Renderer\PhpRenderer;

class Upload implements IngesterInterface
{
    /**
     * @var Uploader
     */
    protected $uploader;

    public function __construct(Uploader $uploader)
    {
        $this->uploader = $uploader;
    }

    public function getLabel()
    {
        return 'Upload'; // @translate
    }

    public function getRenderer()
    {
        return 'file';
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $fileData = $request->getFileData();
        if (!isset($fileData['file'])) {
            $errorStore->addError('error', 'No files were uploaded');
            return;
        }

        if (!isset($data['file_index'])) {
            $errorStore->addError('error', 'No file index was specified');
            return;
        }

        $index = $data['file_index'];
        if (!isset($fileData['file'][$index])) {
            $errorStore->addError('error', 'No file uploaded for the specified index');
            return;
        }

        $tempFile = $this->uploader->upload($fileData['file'][$index], $errorStore);
        if (!$tempFile) {
            return;
        }

        $tempFile->setSourceName($fileData['file'][$index]['name']);
        if (!array_key_exists('o:source', $data)) {
            $media->setSource($fileData['file'][$index]['name']);
        }
        $tempFile->mediaIngestFile($media, $request, $errorStore);
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        $infoTemplate = '
        <div class="media-file-info">
            <div class="media-file-thumbnail"></div>
            <div class="media-file-size">
        </div>';
        return '
        <div class="field">
            <div class="field-meta">
                <label for="media-file-input-__index__">' . $view->translate('Upload file') . '</label>
                <a href="#" class="expand" aria-label="' . $view->translate('Expand') . '" title="' . $view->translate('Expand') . '"></a>
                <div class="collapsible">
                    <div class="field-description">' . $view->uploadLimit() . '</div>
                </div>
            </div>
            <div class="inputs">
                <input type="file" name="file[__index__]" id="media-file-input-__index__" class="media-file-input" data-info-template="' . $view->escapeHtml($infoTemplate) . '" multiple required>
                <input type="hidden" name="o:media[__index__][file_index]" value="__index__">
            </div>
        </div>';
    }
}
