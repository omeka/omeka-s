<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form;
use Laminas\View\Renderer\PhpRenderer;

class Oembed extends AbstractBlockLayout
{
    protected $oembed;

    protected $defaultData = [
        'url' => null,
        'oembed' => null,
        'update' => false,
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
        if (!$data['oembed'] || $data['update']) {
            $data['oembed'] = $this->oembed->getOembed($data['url'], $errorStore);
        }
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $data = $block ? $block->data() + $this->defaultData : $this->defaultData;
        $form = new Form\Form;
        if ($data['oembed']) {
            $form->add([
                'type' => Form\Element\Checkbox::class,
                'name' => 'o:block[__blockIndex__][o:data][update]',
                'options' => [
                    'label' => 'Update oEmbed',
                ],
            ]);
            $form->add([
                'type' => Form\Element\Text::class,
                'name' => 'oembed_url',
                'options' => [
                    'label' => 'oEmbed URL',
                ],
                'attributes' => [
                    'value' => $data['url'],
                    'disabled' => true,
                ],
            ]);
            $form->add([
                'type' => Form\Element\Textarea::class,
                'name' => 'oembed_oembed',
                'options' => [
                    'label' => 'oEmbed',
                ],
                'attributes' => [
                    'value' => json_encode($data['oembed'], JSON_PRETTY_PRINT),
                    'rows' => 6,
                    'disabled' => true,
                ],
            ]);
            $form->add([
                'type' => Form\Element\Hidden::class,
                'name' => 'o:block[__blockIndex__][o:data][url]',
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
        } else {
            $form->add([
                'type' => Form\Element\Text::class,
                'name' => 'o:block[__blockIndex__][o:data][url]',
                'options' => [
                    'label' => 'oEmbed URL', // @translate
                ],
                'attributes' => [
                    'value' => $data['url'],
                    'required' => true,
                ],
            ]);
        }
        return $view->formCollection($form, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data() + $this->defaultData;
        return $this->oembed->renderOembed($data['oembed'], $view);
    }
}
