<?php
namespace Omeka\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class IiifImage extends AbstractBlockLayout
{
    protected $defaultBlockData = [
        'image_url' => null,
    ];

    public function getLabel()
    {
        return 'IIIF image'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        // Build the form.
        $form = new \Laminas\Form\Form;
        $form->add([
            'type' => 'text',
            'name' => 'o:block[__blockIndex__][o:data][image_url]',
            'options' => [
                'label' => 'Image URL', // @translate
            ],
        ]);

        // Set form data.
        $blockData = $this->getBlockData($block);
        $form->setData([
            'o:block[__blockIndex__][o:data][image_url]' => $blockData['image_url'],
        ]);

        // Render the form.
        return $view->formCollection($form);
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        // Sanitize user data.
        $blockData = $block->getData();
        $blockData['image_url'] = is_string($blockData['image_url'])
            ? trim($blockData['image_url'])
            : $this->defaultBlockData['image_url'];
        $block->setData($blockData);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/faceted-browse/block-layout/faceted-browse-preview')
    {
        $blockData = $this->getBlockData($block);
        if (!$blockData['image_url']) {
            return;
        }

        $tileSourcesUrl = $view->assetUrl('vendor/openseadragon/openseadragon.min.js', 'Omeka');
        $prefixUrl = $view->assetUrl('vendor/openseadragon/images/', 'Omeka', false, false);
        $view->headScript()->appendFile($tileSourcesUrl);
        $html = <<<'HTML'
        <div id="%1$s" style="height: 400px;"></div>
        <script type="text/javascript">
            OpenSeadragon({
                id: "%1$s",
                prefixUrl: "%2$s",
                tileSources: "%3$s"
            });
        </script>
        HTML;
        return sprintf(
            $html,
            sprintf('iiif-image-%s', $block->id()),
            $view->escapeJs($prefixUrl),
            $view->escapeJs($blockData['image_url'])
        );
    }

    public function getBlockData(?SitePageBlockRepresentation $block)
    {
        $blockData = $block ? $block->data() : [];
        $blockData = array_merge($this->defaultBlockData, $blockData);
        return $blockData;
    }
}