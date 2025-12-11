<?php
namespace Omeka\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;

class IiifPresentation extends AbstractBlockLayout
{
    protected $defaultBlockData = [
        'manifest_url' => null,
        'title' => null,
        'show_title' => null,
    ];

    public function getLabel()
    {
        return 'IIIF presentation'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, ?SitePageRepresentation $page = null, ?SitePageBlockRepresentation $block = null)
    {
        // Build the form.
        $form = new \Laminas\Form\Form;
        $form->add([
            'type' => 'text',
            'name' => 'o:block[__blockIndex__][o:data][manifest_url]',
            'options' => [
                'label' => 'Manifest URL', // @translate
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
            'o:block[__blockIndex__][o:data][manifest_url]' => $blockData['manifest_url'],
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
        $blockData['manifest_url'] = is_string($blockData['manifest_url'])
            ? trim($blockData['manifest_url'])
            : $this->defaultBlockData['manifest_url'];
        $blockData['title'] = is_string($blockData['title'])
            ? trim($blockData['title'])
            : $this->defaultBlockData['title'];
        $blockData['show_title'] = $blockData['show_title'];
        $block->setData($blockData);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/faceted-browse/block-layout/faceted-browse-preview')
    {
        $blockData = $this->getBlockData($block);
        if (!$blockData['manifest_url']) {
            return;
        }
        $query = [
            'url' => $blockData['manifest_url'],
            'mirador_config' => json_encode([
                'window.sideBarOpen' => false,
            ]),
        ];
        return sprintf(
            '%s%s',
            ($blockData['title'] && $blockData['show_title']) ? sprintf('<h3>%s</h3>', $blockData['title']) : null,
            $view->iiifViewer($query, ['title' => $blockData['title']])
        );
    }

    public function getBlockData(?SitePageBlockRepresentation $block)
    {
        $blockData = $block ? $block->data() : [];
        $blockData = array_merge($this->defaultBlockData, $blockData);
        return $blockData;
    }
}
