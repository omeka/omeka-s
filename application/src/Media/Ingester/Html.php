<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Form\Element\Ckeditor;
use Omeka\Stdlib\HtmlPurifier;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;

class Html implements MutableIngesterInterface
{
    /**
     * @var HtmlPurifier
     */
    protected $purifier;

    public function __construct(HtmlPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    public function updateForm(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        return $this->getForm($view, 'media-html', $media->mediaData()['html']);
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        return $this->getForm($view, 'media-html-__index__');
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'HTML'; // @translate
    }

    public function getRenderer()
    {
        return 'html';
    }

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['html'])) {
            $html = $data['html'];
            $html = $this->purifier->purify($html);
            $data['html'] = $html;
            $media->setData($data);
        }
    }

    public function update(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['o:media']['__index__']['html'])) {
            $html = $data['o:media']['__index__']['html'];
            $html = $this->purifier->purify($html);
            $media->setData(['html' => $html]);
        }
    }

    /**
     * Get the HTML editor textarea markup.
     *
     * @param PhpRenderer $view
     * @param string $id HTML ID for the textarea
     * @param string $value Value to pre-fill
     *
     * @return string
     */
    protected function getForm(PhpRenderer $view, $id, $value = '')
    {
        $view->ckEditor();
        $textarea = new Ckeditor('o:media[__index__][html]');
        $textarea->setOptions([
            'label' => 'HTML', // @translate
            'info' => 'HTML or plain text.', // @translate
        ]);
        $textarea->setAttributes([
            'rows' => 15,
            'id' => $id,
            'class' => 'media-html',
            'value' => $value,
        ]);
        return $view->formRow($textarea);
    }
}
