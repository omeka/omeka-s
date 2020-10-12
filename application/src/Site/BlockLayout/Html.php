<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\HtmlPurifier;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Html extends AbstractBlockLayout
{
    /**
     * @var HtmlPurifier
     */
    protected $htmlPurifier;

    public function __construct(HtmlPurifier $htmlPurifier)
    {
        $this->htmlPurifier = $htmlPurifier;
    }

    public function getLabel()
    {
        return 'HTML'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $html = isset($data['html']) ? $this->htmlPurifier->purify($data['html']) : '';
        $data['html'] = $html;
        $block->setData($data);
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {

        $form = new Form();
        $div_class = new Element\Text("o:block[__blockIndex__][o:data][divclass]");
        $div_class->setOptions([
                'label' => 'Class', // @translate
                'info' => 'Optional CSS class for styling HTML.', // @translate
            ]);
        $html = new Element\Textarea("o:block[__blockIndex__][o:data][html]");
        $html->setAttribute('class', 'block-html full wysiwyg');
        if ($block) {
            $html->setValue($block->dataValue('html'));
            $div_class->setValue($block->dataValue('divclass'));
        }
        $form->add($div_class);
        $form->add($html);

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $html_block = $block->dataValue('html', '');
        $div = $block->dataValue('divclass');
        if (!empty($div)) {
            //wrap HTML in div with specified class, if present
            $html_final = '<div class="' . $div . '">';
            $html_final .= $html_block;
            $html_final .= '</div>';
        } else {
            $html_final = $html_block;
        }

        return $html_final;
    }

    public function getFulltextText(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return strip_tags($this->render($view, $block));
    }
}
