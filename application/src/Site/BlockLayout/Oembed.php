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
        $data['oembed'] = $this->oembed->getOembed($data['url'], $errorStore);
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $data = $block ? $block->data() + $this->defaultData : $this->defaultData;
        $form = new Form\Form;
        $form->add([
            'type' => Form\Element\Text::class,
            'name' => 'o:block[__blockIndex__][o:data][url]',
            'options' => [
                'label' => 'oEmbed URL', // @translate
            ],
            'attributes' => [
                'value' => $data['url'],
            ],
        ]);
        return $view->formCollection($form, false);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $data = $block->data() + $this->defaultData;
        return $this->oembed->renderOembed($data['oembed'], $view);
    }
}
