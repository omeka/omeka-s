<?php 
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class OpenSeadragonHandler extends AbstractHandler
{
	public function getLabel()
	{
		$translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('Open Seadragon Image');
	}

	public function ingest(Media $media, Request $request, ErrorStore $errorStore)
	{

	}

	public function form(PhpRenderer $view, array $options = array())
	{
		$urlInput = new Text('o:media[__index__][o:source]');
        $urlInput->setOptions(array(
            'label' => $view->translate('Open Seadragon Image URL'),
            'info' => $view->translate('URL for the image to embed.'),
        ));
        return $view->formField($urlInput);
	}

	public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
	{

	}
}