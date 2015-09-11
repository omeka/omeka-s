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
		$data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No Open Seadragon Image URL specified');
            return;
        }

        $source = $data['o:source'];

        $media->setSource($source);
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
		$source = $view->escapeJs($media->source());

		$view->headScript()->appendFile($view->assetUrl('js/openseadragon/openseadragon.min.js', 'Omeka'));
		$prefixUrl = $view->assetUrl('js/openseadragon/images/', 'Omeka');

		$image =
			'<div id="openseadragon1" style="width: 800px; height: 600px;"></div>
			<script type="text/javascript">
			    var viewer = OpenSeadragon({
			        id: "openseadragon1",
			        prefixUrl: "'. $prefixUrl . '",
			        tileSources: "' . $source . '"
			    });
			</script>'
		;

		return $image;
	}
}