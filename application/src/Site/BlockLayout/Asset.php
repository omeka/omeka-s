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
        $pages = ($block) ? $block->data() : '';
        $assets = [];
        if ($pages !== '') {
          foreach ($pages as $key => $page) {
            if (isset($page['site_page']['media']) && ($page['site_page']['media'] !== '')) {
              $assetId = $page['site_page']['media'];
              $asset = $view->api()->read('assets', $assetId)->getContent();
              $assets[$assetId] = $asset;
            }
          }
        }
        return $view->partial('common/asset', [
          'block' => $block,
          'siteId' => $siteId,
          'apiUrl' => $apiUrl,
          'pages' => $pages,
          'assets' => $assets,
        ]);
    }
    
    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $pages = $block->data();
        $pages = ($block) ? $block->data() : '';
        $assets = [];
        if ($pages !== '') {
          foreach ($pages as $key => $page) {
            if (isset($page['site_page']['media']) && ($page['site_page']['media'] !== '')) {
              $assetId = $page['site_page']['media'];
              $asset = $view->api()->read('assets', $assetId)->getContent();
              $assets[$assetId] = $asset;
            }
          }
        }
        return $view->partial('common/block-layout/asset', [
          'pages' => $pages,
          'assets' => $assets,
        ]);
    }
}