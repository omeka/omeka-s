<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form;
use Laminas\View\Renderer\PhpRenderer;

class Oembed extends AbstractBlockLayout implements TemplateableBlockLayoutInterface
{
    protected $oembed;

    protected $defaultData = [
        'url' => null,
        'url_old' => null,
        'oembed' => null,
        'refresh' => false,
    ];

    public function __construct($oembed)
    {
        $this->oembed = $oembed;
    }

    public function getLabel()
    {
        return 'oEmbed'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData() + $this->defaultData;
        if (is_string($data['oembed'])) {
            $data['oembed'] = json_decode($data['oembed'], true);
        }
        // Get the oEmbed response when a) the block is new, b) the user wants
        // to referesh the existing oEmbed, c) the user changes the oEmbed URL.
        if (!$data['oembed'] || $data['refresh'] || $data['url'] !== $data['url_old']) {
            $data['oembed'] = $this->oembed->getOembed($data['url'], $errorStore);
        }
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $data = $block ? $block->data() + $this->defaultData : $this->defaultData;
        $form = new Form\Form;

        $form->add([
            'type' => Form\Element\Url::class,
            'name' => 'o:block[__blockIndex__][o:data][url]',
            'options' => [
                'label' => 'oEmbed URL', // @translate
            ],
            'attributes' => [
                'value' => $data['url'],
                'required' => true,
            ],
        ]);
        if (!$data['oembed']) {
            return $view->formCollection($form, false);
        }
        $form->add([
            'type' => Form\Element\Url::class,
            'name' => 'o:block[__blockIndex__][o:data][url]',
            'options' => [
                'label' => 'oEmbed URL', // @translate
            ],
            'attributes' => [
                'value' => $data['url'],
                'required' => true,
            ],
        ]);
        $form->add([
            'type' => Form\Element\Textarea::class,
            'name' => 'oembed_oembed',
            'options' => [
                'label' => 'oEmbed',
            ],
            'attributes' => [
                'value' => json_encode($data['oembed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'rows' => 8,
                'disabled' => true,
            ],
        ]);
        $form->add([
            'type' => Form\Element\Checkbox::class,
            'name' => 'o:block[__blockIndex__][o:data][refresh]',
            'options' => [
                'label' => 'Refresh oEmbed',
            ],
        ]);
        $form->add([
            'type' => Form\Element\Hidden::class,
            'name' => 'o:block[__blockIndex__][o:data][url_old]',
            'attributes' => [
                'value' => $data['url'],
            ],
        ]);
        $form->add([
            'type' => Form\Element\Hidden::class,
            'name' => 'o:block[__blockIndex__][o:data][oembed]',
            'attributes' => [
                'value' => json_encode($data['oembed']),
            ],
        ]);
        return sprintf(
            '%s<a href="#" class="expand" aria-label="expand"><h4>%s</h4></a><div class="collapsible">%s%s%s%s</div>',
            $view->formRow($form->get('o:block[__blockIndex__][o:data][url]')),
            $view->translate('Advanced'),
            $view->formRow($form->get('oembed_oembed')),
            $view->formRow($form->get('o:block[__blockIndex__][o:data][refresh]')),
            $view->formRow($form->get('o:block[__blockIndex__][o:data][oembed]')),
            $view->formRow($form->get('o:block[__blockIndex__][o:data][url_old]')),
        );
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block, $templateViewScript = 'common/block-layout/oembed')
    {
        return $view->partial($templateViewScript, [
            'block' => $block,
            'oembed' => $this->oembed,
            'data' => $block->data() + $this->defaultData,
        ]);
    }
}
