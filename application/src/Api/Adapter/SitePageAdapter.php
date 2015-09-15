<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\SiteBlockAttachment;
use Omeka\Entity\SitePage;
use Omeka\Entity\SitePageBlock;
use Omeka\Stdlib\ErrorStore;

class SitePageAdapter extends AbstractEntityAdapter
{
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
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && isset($data['o:site']['o:id'])
        ) {
            $site = $this->getAdapter('sites')->findEntity($data['o:site']['o:id']);
            $this->authorize($site, 'add-page');
            $entity->setSite($site);
        }
        if ($this->shouldHydrate($request, 'o:slug')) {
            $entity->setSlug($request->getValue('o:slug'));
        }
        if ($this->shouldHydrate($request, 'o:title')) {
            $entity->setTitle($request->getValue('o:title'));
        }

        $appendBlocks = $request->getOperation() === Request::UPDATE
            && $request->isPartial();
        $this->hydrateBlocks($request->getValue('o:block', array()), $entity, $errorStore,
            $appendBlocks);
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getSite()) {
            $errorStore->addError('o:site', 'A page must belong to a site.');
        }

        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.');
        }
        if (preg_match('/[^a-zA-Z0-9-]/u', $slug)) {
            $errorStore->addError('o:slug',
                'A slug can only contain letters, numbers, and hyphens.');
        }
        if ($entity->getSite() && !$this->isUnique($entity, array(
                'slug' => $slug,
                'site' => $entity->getSite()
        ))) {
            $errorStore->addError('o:slug', sprintf(
                'The slug "%s" is already taken.',
                $slug
            ));
        }

        if (!$entity->getTitle()) {
            $errorStore->addError('o:title', 'A page must have a title.');
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
        $newBlocks = array();
        $position = 1;
        $fallbackBlock = array(
            'o:layout' => null,
            'o:data' => array()
        );

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
                $errorStore->addError('o:block', 'All blocks must have a layout.');
                return;
            }
            if (!is_array($inputBlock['o:data'])) {
                $errorStore->addError('o:block', 'Block data must not be a scalar value.');
                return;
            }

            $block->setLayout($inputBlock['o:layout']);
            $block->setData($inputBlock['o:data']);

            // (Re-)order blocks by their order in the input
            $block->setPosition($position++);

            $attachmentData = isset($inputBlock['o:attachment'])
                ? $inputBlock['o:attachment'] : array();

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
     * @return boolean true on success, false on error
     */
    private function hydrateAttachments(array $attachmentData, SitePageBlock $block,
        ErrorStore $errorStore)
    {
        $itemAdapter = $this->getAdapter('items');
        $attachments = $block->getAttachments();
        $existingAttachments = $attachments->toArray();
        $newAttachments = array();
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

            if (!isset($inputAttachment['o:item']['o:id'])) {
                $errorStore->addError('o:attachment', 'Attachments must specify an item to attach.');
                return false;
            }

            $item = $itemAdapter->findEntity($inputAttachment['o:item']['o:id']);

            if (isset($inputAttachment['o:media']['o:id'])) {
                $itemMedia = $item->getMedia();
                $media = $itemMedia->get($inputAttachment['o:media']['o:id']);
            } else {
                $media = null;
            }

            $caption = isset($inputAttachment['o:caption']) ? $inputAttachment['o:caption'] : '';

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
