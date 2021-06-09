<?php
namespace Omeka\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Asset extends AbstractBlockLayout
{
    public function getLabel() {
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
    
    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $escape = $view->plugin('escapeHtml');
        $html = '';
        $siteId = $site->id();
        $apiUrl = $site->apiUrl();
        $block = ($block) ? $block->data() : '';
        $assets = [];
        if ($block !== '') {  
          foreach ($block as $value) {
            if (isset($value['id'])) {
              $assetId = $value['id'];
              $asset = $view->api()->read('assets', $assetId)->getContent();
              $assets[$assetId] = $asset;
            }
          }
        }
        return $view->partial('common/asset', [
          'block' => $block,
          'siteId' => $siteId,
          'apiUrl' => $apiUrl,
          'assets' => $assets,
        ]);
    }
    
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $block = ($block) ? $block->data() : '';
        $assets = [];
        if ($block !== '') {
          foreach ($block as $value) {
            if (isset($value['id'])) {
              $assetId = $value['id'];
              $asset = $view->api()->read('assets', $assetId)->getContent();
              $assets[] = $asset;
            }
            else {
              $className = $value;
            }
          }
        }
        return $view->partial('common/block-layout/asset', [
          'assets' => $assets,
          'className' => $className
        ]);
    }
}