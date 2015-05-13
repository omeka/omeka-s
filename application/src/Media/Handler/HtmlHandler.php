<?php
namespace Omeka\Media\Handler;

use Zend\Db\Sql\Ddl\Column\Text;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\AbstractHandler;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\View\Renderer\PhpRenderer;
use Zend\Form\Element\Textarea;

class HtmlHandler extends AbstractHandler
{

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {
        $textarea = new Textarea('html[__index__]');
        $textarea->setOptions(array(
            'label' => $view->translate('HTML'),
            'info'  => $view->translate('HTML or plain text.'),
        ));
        
        $textarea->setAttribute('rows', 25);
        
        $field = $view->formField($textarea);
        return $field;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('HTML');
    }
    
    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $text = "whatev";
        $media->setData(array('html' => $text));
    }
    
    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        
    }

    /**
     * {@inheritDoc}
     */
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
    {
        $data = $media->mediaData();
        return $data['html'];
    }
}