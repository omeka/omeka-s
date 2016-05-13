<?php
namespace Omeka\Media\Ingester;

use Zend\Form\Element\Hidden;
use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Service\HtmlPurifier;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;
use Zend\Form\Element\Textarea;
use Zend\Form\Element\Text as TextInput;

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
        $titleInput = new TextInput('o:media[__index__][dcterms:title][0][@value]');
        $titlePropertyInput = new Hidden('o:media[__index__][dcterms:title][0][property_id]');
        $titleTypeInput = new Hidden('o:media[__index__][dcterms:title][0][type]');
        //make sure we have correct dcterms:title id
        $api = $view->api();
        $dctermsTitle = $api->search('properties', ['term'=> 'dcterms:title'])->getContent()[0];
        $titlePropertyInput->setValue($dctermsTitle->id());
        $titleTypeInput->setValue('literal');
        $titleInput->setOptions([
            'label' => 'Title', // @translate
            'info'  => 'A title for the HTML content' // @translate
        ]);
        $html = $view->formRow($titleInput);
        $html .= $view->formRow($titlePropertyInput);
        $html .= $view->formRow($titleTypeInput);
        $html .= $this->getForm($view, 'media-html-__index__');
        return $html;
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
            $serviceLocator = $this->getServiceLocator();
            $html = $this->purifier->purify($html);
            $data['html'] = $html;
            $media->setData($data);
        }
    }

    public function update(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $html = $data['o:media']['__index__']['html'];
        $serviceLocator = $this->getServiceLocator();
        $html = $this->purifier->purify($html);
        $media->setData(['html' => $html]);
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
        $textarea = new Textarea('o:media[__index__][html]');
        $textarea->setOptions([
            'label' => 'HTML', // @translate
            'info'  => 'HTML or plain text.', // @translate
        ]);
        $textarea->setAttributes([
            'rows'     => 15,
            'id'       => $id,
            'required' => true,
            'class'    => 'media-html',
            'value'    => $value
        ]);
        $field = $view->formRow($textarea);
        $field .= "
            <script type='text/javascript'>
                $('#$id').ckeditor();
            </script>
        ";
        return $field;
    }
}
