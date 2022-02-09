<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Exception as ApiException;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Element\Select;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Asset extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Asset'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('js/asset-form.js', 'Omeka'));
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $block->setData($data);
    }

    public function alignmentClassSelect(PhpRenderer $view, SitePageBlockRepresentation $block = null
    ) {
        $alignmentLabels = [
          'default', // @translate
          'float left', // @translate
          'float right', // @translate
          'center', // @translate
        ];
        $alignmentValues = [
          'default', // @translate
          'left', // @translate
          'right', // @translate
          'center', // @translate
        ];
        $alignment = $block ? $block->dataValue('alignment', 'default') : 'default';
        $select = new Select('o:block[__blockIndex__][o:data][alignment]');
        $select->setValueOptions(array_combine($alignmentValues, $alignmentLabels))->setValue($alignment);
        $selectLabel = 'Alignment'; // @translate
        $select->setAttributes(['title' => $selectLabel, 'aria-label' => $selectLabel]);
        $html = '<div class="field"><div class="field-meta">';
        $html .= '<label for="o:block[__blockIndex__][o:data][alignment]">' . $selectLabel . '</label></div>';
        $html .= '<div class="inputs">' . $view->formSelect($select) . '</div></div>';
        return $html;
    }

    public function prepareAssetAttachments(PhpRenderer $view, $blockData, SiteRepresentation $site)
    {
        $attachments = [];
        $sitePages = $site->pages();
        $sitePageArray = [];
        foreach ($sitePages as $sitePage) {
            $sitePageArray[$sitePage->id()] = $sitePage;
        }
        if ($blockData) {
            foreach ($blockData as $key => $value) {
                if (isset($value['id'])) {
                    if ($value['id'] !== '') {
                        $assetId = $value['id'];
                        try {
                            $asset = $view->api()->read('assets', $assetId)->getContent();
                            $attachments[$key]['asset'] = $asset;
                        } catch (ApiException\NotFoundException $e) {
                            $attachments[$key]['asset'] = null;
                        }
                    } else {
                        $attachments[$key]['asset'] = null;
                    }
                    if ($value['page'] !== '') {
                        $linkPageId = $value['page'];
                        $attachments[$key]['page'] = (isset($sitePageArray[$linkPageId])) ? $sitePageArray[$linkPageId] : null;
                    }
                    $attachments[$key]['alt_link_title'] = $value['alt_link_title'];
                    $attachments[$key]['caption'] = $value['caption'];
                }
            }
        }
        return $attachments;
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $siteId = $site->id();
        $apiUrl = $site->apiUrl();
        $blockData = ($block) ? $block->data() : '';
        $attachments = $this->prepareAssetAttachments($view, $blockData, $site);
        $alignmentClassSelect = $this->alignmentClassSelect($view, $block);
        return $view->partial('common/asset-block-form', [
          'block' => $blockData,
          'siteId' => $siteId,
          'apiUrl' => $apiUrl,
          'attachments' => $attachments,
          'alignmentClassSelect' => $alignmentClassSelect,
        ]);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $blockData = ($block) ? $block->data() : '';
        $site = $view->site;
        $attachments = $this->prepareAssetAttachments($view, $blockData, $site);
        $customClass = $block->dataValue('className');
        $alignment = $block->dataValue('alignment');
        return $view->partial('common/block-layout/asset', [
          'attachments' => $attachments,
          'className' => $customClass,
          'alignment' => $alignment,
        ]);
    }
}
