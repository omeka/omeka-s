<?php
namespace Omeka\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class IiifPresentation extends AbstractBlockLayout
{
    protected $defaultBlockData = [
        'manifest_url' => null,
    ];

    public function getLabel()
    {
        return 'IIIF presentation'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
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

        // Set form data.
        $blockData = $this->getBlockData($block);
        $form->setData([
            'o:block[__blockIndex__][o:data][manifest_url]' => $blockData['manifest_url'],
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
                'window.sideBarOpen' => true,
            ]),
        ];
        return $view->iiifViewer($query, []);
    }

    public function getBlockData(?SitePageBlockRepresentation $block)
    {
        $blockData = $block ? $block->data() : [];
        $blockData = array_merge($this->defaultBlockData, $blockData);
        return $blockData;
    }
}