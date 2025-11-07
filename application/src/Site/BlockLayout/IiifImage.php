<?php
namespace Omeka\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;

class IiifImage extends AbstractBlockLayout
{
    protected $defaultBlockData = [
        'image_url' => null,
        'title' => null,
        'show_title' => null,
    ];

    public function getLabel()
    {
        return 'IIIF image'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, ?SitePageRepresentation $page = null, ?SitePageBlockRepresentation $block = null)
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
        $form->add([
            'type' => 'text',
            'name' => 'o:block[__blockIndex__][o:data][title]',
            'options' => [
                'label' => 'Title', // @translate
            ],
        ]);
        $form->add([
            'type' => 'checkbox',
            'name' => 'o:block[__blockIndex__][o:data][show_title]',
            'options' => [
                'label' => 'Show title', // @translate
                'info' => 'Check to show the title as a heading. For accessibility, the title will always be available to screen readers.', // @translate
            ],
        ]);

        // Set form data.
        $blockData = $this->getBlockData($block);
        $form->setData([
            'o:block[__blockIndex__][o:data][image_url]' => $blockData['image_url'],
            'o:block[__blockIndex__][o:data][title]' => $blockData['title'],
            'o:block[__blockIndex__][o:data][show_title]' => $blockData['show_title'],
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
        $blockData['title'] = is_string($blockData['title'])
            ? trim($blockData['title'])
            : $this->defaultBlockData['title'];
        $blockData['show_title'] = $blockData['show_title'];
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
        %s
        <div id="%s" style="height: 400px;" role="group" aria-label="%s"></div>
        <script type="text/javascript">
            OpenSeadragon({
                id: "%s",
                prefixUrl: "%s",
                tileSources: "%s"
            });
        </script>
        HTML;
        return sprintf(
            $html,
            ($blockData['title'] && $blockData['show_title']) ? sprintf('<h3>%s</h3>', $blockData['title']) : null,
            sprintf('iiif-image-%s', $block->id()),
            $blockData['title'],
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
