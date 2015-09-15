<?php 
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class IIIFHandler extends AbstractHandler
{
	public function getLabel()
	{
		$translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('IIIF Image');
	}

	public function ingest(Media $media, Request $request, ErrorStore $errorStore)
	{
		$data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No IIIF Image URL specified');
            return;
        }

        $source = $data['o:source'];

	    //Make a request and handle any errors that might occur.

        $uri = new HttpUri($source);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', "Invalid url specified");
            return false;
        }
        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($uri);
        $response = $client->send();
        if (!$response->isOk()) {
            $errorStore->addError('o:source', sprintf(
                "Error reading %s: %s (%s)",
                $type,
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return false;
        }

        $IIIFData = json_decode($response->getBody(), true);
        if(!$IIIFData) {
        	$errorStore->addError('o:source', 'Error decoding IIIF JSON');
        	return;
        }

        //Check API version and generate a thumbnail

        //Version 2.0
        if (isset($IIIFData['@context']) && $IIIFData['@context'] == 'http://iiif.io/api/image/2/context.json') {
            $URLString = '/full/full/0/default.jpg';
        // Earlier versions
        } else  {
        	$URLString = '/full/full/0/native.jpg';
        }


        if (isset($IIIFData['@id'])) {
            $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
            $file = $this->getServiceLocator()->get('Omeka\File');

            $this->downloadFile($IIIFData['@id'] . $URLString, $file->getTempPath());
            $hasThumbnails = $fileManager->storeThumbnails($file);

            if ($hasThumbnails) {
                $media->setFilename($file->getStorageName());
                $media->setHasThumbnails(true);
            }
        }

        $media->setSource($source);
        $media->setData($IIIFData);
	}

	public function form(PhpRenderer $view, array $options = array())
	{
		$urlInput = new Text('o:media[__index__][o:source]');
        $urlInput->setOptions(array(
            'label' => $view->translate('IIIF Image URL'),
            'info' => $view->translate('URL for the image to embed.'),
        ));
        return $view->formField($urlInput);
	}

	public function render(PhpRenderer $view, MediaRepresentation $media, array $options = array())
	{
		$source = $view->escapeJs($media->source());

		$IIIFData = $view->escapeJs($media->data());

		$view->headScript()->appendFile($view->assetUrl('js/openseadragon/openseadragon.min.js', 'Omeka'));
		$prefixUrl = $view->assetUrl('js/openseadragon/images/', 'Omeka');
		if ($themeheight){
			$height = $themeheight;
			}
		else{
			$height = '600px';
		};

		$image =
			'<div class="openseadragon"></div>
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