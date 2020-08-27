<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SiteBlockAttachment;
use Omeka\Entity\SitePage;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class SitePageAdapter extends AbstractEntityAdapter implements FulltextSearchableInterface
{
    use SiteSlugTrait;

    protected $sortFields = [
        'id' => 'id',
        'created' => 'created',
        'modified' => 'modified',
        'title' => 'title',
        'slug' => 'slug',
    ];

    public function getResourceName()
    {
        return 'site_pages';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\SitePageRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\SitePage::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['site_id']) && is_numeric($query['site_id'])) {
            $siteAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.site',
                $siteAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$siteAlias.id",
                $this->createNamedParameter($qb, $query['site_id']))
            );
        }

        if (isset($query['item_id']) && is_numeric($query['item_id'])) {
            $blocksAlias = $this->createAlias();
            $qb->innerJoin('omeka_root.blocks', $blocksAlias);
            $attachmentsAlias = $this->createAlias();
            $qb->innerJoin("$blocksAlias.attachments", $attachmentsAlias);
            $qb->andWhere($qb->expr()->eq(
                "$attachmentsAlias.item",
                $this->createNamedParameter($qb, $query['item_id']))
            );
        }

        if (isset($query['slug'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.slug',
                $this->createNamedParameter($qb, $query['slug'])
            ));
        }

        if (isset($query['is_public'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.isPublic',
                $this->createNamedParameter($qb, (bool) $query['is_public'])
            ));
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $title = null;
        $data = $request->getContent();
        $blockData = $request->getValue('o:block', []);

        if (Request::CREATE === $request->getOperation() && isset($data['o:site']['o:id'])) {
            $site = $this->getAdapter('sites')->findEntity($data['o:site']['o:id']);
            $this->authorize($site, 'add-page');
            $entity->setSite($site);

            if (!$blockData) {
                // Add the pageTitle block to all new pages.
                $blockData = [[
                    'o:layout' => 'pageTitle',
                    'o:data' => [],
                ]];
            }
        }

        if ($this->shouldHydrate($request, 'o:title')) {
            $title = trim($request->getValue('o:title', ''));
            $entity->setTitle($title);
        }

        if ($this->shouldHydrate($request, 'o:slug')) {
            $slug = trim($request->getValue('o:slug', ''));
            if ($slug === ''
                && $request->getOperation() === Request::CREATE
                && is_string($title) && $title !== ''
                && isset($site)
            ) {
                $slug = $this->getAutomaticSlug($title, $site);
            }
            $entity->setSlug($slug);
        }

        if ($this->shouldHydrate($request, 'o:is_public')) {
            $entity->setIsPublic($request->getValue('o:is_public', true));
        }

        $appendBlocks = $request->getOperation() === Request::UPDATE && $request->getOption('isPartial', false);
        $this->hydrateBlocks($blockData, $entity, $errorStore, $appendBlocks);
        $this->updateTimestamps($request, $entity);
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getSite()) {
            $errorStore->addError('o:site', 'A page must belong to a site.'); // @translate
        }

        $title = $entity->getTitle();
        if (!is_string($title) || $title === '') {
            $errorStore->addError('o:title', 'A page must have a title.'); // @translate
        }

        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.'); // @translate
        }
        if (preg_match('/[^a-zA-Z0-9_-]/u', $slug)) {
            $errorStore->addError('o:slug', 'A slug can only contain letters, numbers, underscores, and hyphens.'); // @translate
        }
        $site = $entity->getSite();
        if ($site && $site->getId() && !$this->isUnique($entity, [
                'slug' => $slug,
                'site' => $entity->getSite(),
        ])) {
            $errorStore->addError('o:slug', new Message(
                'The slug "%s" is already taken.', // @translate
                $slug
            ));
        }
    }

    /**
     * Hydrate block data for the page.
     *
     * @param array $blockData
     * @param SitePage $page
     * @param bool $append
     */
    private function hydrateBlocks(array $blockData, SitePage $page, ErrorStore $errorStore,
        $append = false)
    {
        $blocks = $page->getBlocks();
        $existingBlocks = $blocks->toArray();
        $newBlocks = [];
        $position = 1;
        $fallbackBlock = [
            'o:layout' => null,
            'o:data' => [],
        ];

        foreach ($blockData as $inputBlock) {
            if (!is_array($inputBlock)) {
                continue;
            }

            $inputBlock = array_merge($fallbackBlock, $inputBlock);

            $block = current($existingBlocks);
            if ($block === false || $append) {
                $block = new SitePageBlock;
                $block->setPage($page);
                $newBlocks[] = $block;
            } else {
                // Null out values as we re-use them
                $existingBlocks[key($existingBlocks)] = null;
                next($existingBlocks);
            }

            if (!is_string($inputBlock['o:layout']) || $inputBlock['o:layout'] === '') {
                $errorStore->addError('o:block', 'All blocks must have a layout.'); // @translate
                return;
            }
            if (!is_array($inputBlock['o:data'])) {
                $errorStore->addError('o:block', 'Block data must not be a scalar value.'); // @translate
                return;
            }

            $block->setLayout($inputBlock['o:layout']);
            $block->setData($inputBlock['o:data']);

            // (Re-)order blocks by their order in the input
            $block->setPosition($position++);

            $attachmentData = isset($inputBlock['o:attachment'])
                ? $inputBlock['o:attachment'] : [];

            // Hydrate attachments, and abort block hydration if there's an error
            if (!$this->hydrateAttachments($attachmentData, $block, $errorStore)) {
                return;
            }

            $handler = $this->getServiceLocator()
                ->get('Omeka\BlockLayoutManager')
                ->get($inputBlock['o:layout'])
                ->onHydrate($block, $errorStore);
        }

        // Remove any blocks that weren't reused
        if (!$append) {
            foreach ($existingBlocks as $key => $existingBlock) {
                if ($existingBlock !== null) {
                    $blocks->remove($key);
                }
            }
        }

        // Add any new blocks that had to be created
        foreach ($newBlocks as $newBlock) {
            $blocks->add($newBlock);
        }
    }

    /**
     * Hydrate attachment data for a block
     *
     * @param array $attachmentData
     * @param SitePageBlock $block
     * @param ErrorStore $errorStore
     * @return bool true on success, false on error
     */
    private function hydrateAttachments(array $attachmentData, SitePageBlock $block,
        ErrorStore $errorStore)
    {
        $itemAdapter = $this->getAdapter('items');
        $attachments = $block->getAttachments();
        $existingAttachments = $attachments->toArray();
        $newAttachments = [];
        $position = 1;

        foreach ($attachmentData as $inputAttachment) {
            if (!is_array($inputAttachment)) {
                continue;
            }

            $attachment = current($existingAttachments);
            if ($attachment === false) {
                $attachment = new SiteBlockAttachment;
                $attachment->setBlock($block);
                $newAttachments[] = $attachment;
            } else {
                // Null out values as we re-use them
                $existingAttachments[key($existingAttachments)] = null;
                next($existingAttachments);
            }

            try {
                $item = $itemAdapter->findEntity($inputAttachment['o:item']['o:id']);
            } catch (NotFoundException $e) {
                $item = null;
            }

            if ($item && isset($inputAttachment['o:media']['o:id'])) {
                $itemMedia = $item->getMedia();
                $media = $itemMedia->get($inputAttachment['o:media']['o:id']);
            } else {
                $media = null;
            }

            $caption = isset($inputAttachment['o:caption']) ? $inputAttachment['o:caption'] : '';
            $purifier = $this->getServiceLocator()->get('Omeka\HtmlPurifier');
            $caption = $purifier->purify($caption);

            $attachment->setItem($item);
            $attachment->setMedia($media);
            $attachment->setCaption($caption);
            $attachment->setPosition($position++);
        }

        // Remove any blocks that weren't reused
        foreach ($existingAttachments as $key => $existingAttachment) {
            if ($existingAttachment !== null) {
                $attachments->remove($key);
            }
        }

        // Add any new blocks that had to be created
        foreach ($newAttachments as $newAttachment) {
            $attachments->add($newAttachment);
        }

        return true;
    }

    public function getFulltextOwner($resource)
    {
        return $resource->getSite()->getOwner();
    }

    public function getFulltextIsPublic($resource)
    {
        // The page is public only if the site and the page are public.
        return $resource->isPublic()
            && $resource->getSite()->isPublic();
    }

    public function getFulltextTitle($resource)
    {
        return $resource->getTitle();
    }

    public function getFulltextText($resource)
    {
        $services = $this->getServiceLocator();
        $layouts = $services->get('Omeka\BlockLayoutManager');
        $view = $services->get('ViewRenderer');
        $text = [];
        foreach ($resource->getBlocks() as $block) {
            $layout = $layouts->get($block->getLayout());
            $blockRepresentation = new SitePageBlockRepresentation($block, $services);
            $text[] = $layout->getFulltextText($view, $blockRepresentation);
            foreach ($block->getAttachments() as $attachment) {
                $item = $attachment->getItem();
                if ($item) {
                    $text[] = $item->getTitle();
                }
                $text[] = strip_tags($attachment->getCaption());
            }
        }
        return implode("\n", array_filter($text));
    }
}
