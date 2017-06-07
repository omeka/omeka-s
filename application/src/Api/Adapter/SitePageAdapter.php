<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SiteBlockAttachment;
use Omeka\Entity\SitePage;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class SitePageAdapter extends AbstractEntityAdapter
{
    use SiteSlugTrait;

    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'created' => 'created',
        'modified' => 'modified',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'site_pages';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\SitePageRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\SitePage';
    }

    /**
     * {@inheritDoc}
     */
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

        $appendBlocks = $request->getOperation() === Request::UPDATE && $request->getOption('isPartial', false);
        $this->hydrateBlocks($blockData, $entity, $errorStore, $appendBlocks);
        $this->updateTimestamps($request, $entity);
    }

    /**
     * {@inheritDoc}
     */
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
}
