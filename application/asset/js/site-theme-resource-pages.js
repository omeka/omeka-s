$(document).ready(function() {

    const form = $('#resource-page-config-form');
    const resourcePageBlocks = form.data('resourcePageBlocks');
    const resourcePageRegions = form.data('resourcePageRegions');
    const blockLayoutLabels = form.data('blockLayoutLabels');

    /**
     * Add a block to a resource page.
     * @param {string} resourceName
     * @param {string} regionName
     * @param {string} blockLayoutName
     */
    const addBlock = function(resourceName, regionName, blockLayoutName) {
        const block = $(form.data('blockTemplate'));
        const blocks = getBlocksRegion(resourceName, regionName);
        const blockLayoutButton = $('button.option')
            .filter(`[data-resource-name="${resourceName}"]`)
            .filter(`[data-block-layout-name="${blockLayoutName}"]`);
        const blockSelector = $(`#block-selector-${resourceName}`);
        let blockLayoutLabel = blockLayoutLabels[blockLayoutName];
        if (!blockLayoutLabel) {
            blockLayoutLabel = `${Omeka.jsTranslate('Unknown block layout')} [${blockLayoutName}]`;
        }

        // Add the block to the region.
        setBlockInputName(block, resourceName, regionName);
        block.find('.block-layout-label').text(blockLayoutLabel);
        block.find('.block-layout-name').val(blockLayoutName);
        blocks.append(block);

        // Handle block selector display.
        blockLayoutButton.prop('disabled', true);
        blockSelector.find('.no-block-layouts').css(
            'display',
            blockSelector.find('button.option').not(':disabled').length ? 'none' : 'inline'
        );
    };

    /**
     * Set the block input name.
     * @param {object} block
     * @param {string} resourceName
     * @param {string} regionName
     */
    const setBlockInputName = function(block, resourceName, regionName) {
        const blockNameInput = block.find('.block-layout-name');
        blockNameInput.attr('name', `resource_page_blocks[${resourceName}][${regionName}][]`);
    };

    /**
     * Get a blocks region.
     * @param {string} resourceName
     * @param {string} regionName
     * @returns
     */
    const getBlocksRegion = function(resourceName, regionName) {
        return $('ul.blocks')
            .filter(`[data-resource-name="${resourceName}"]`)
            .filter(`[data-region-name="${regionName}"]`);
    };

    // Open the correct sidebar on load.
    switch (window.location.hash.substring(1)) {
        case 'section-media':
            Omeka.openSidebar($('#block-selector-media'));
            break;
        case 'section-item_sets':
            Omeka.openSidebar($('#block-selector-item_sets'));
            break;
        case 'section-items':
        default:
            Omeka.openSidebar($('#block-selector-items'));
    }

    // Prepare the page on load.
    $.each(['items', 'item_sets', 'media'], function(index, resourceName) {
        // Populate the block layout lists on load.
        $.each(resourcePageBlocks[resourceName], function(regionName, blockLayoutNames) {
            const blocks = getBlocksRegion(resourceName, regionName);
            if (!blocks.length) {
                // There is no corresponding list for this region. Continue to next region.
                return;
            }
            $.each(blockLayoutNames, function(index, blockLayoutName) {
                addBlock(resourceName, regionName, blockLayoutName);
            });
        });
        // Make blocks sortable on load.
        $.each(resourcePageRegions[resourceName], function(regionName, regionLabel) {
            const blocks = getBlocksRegion(resourceName, regionName);
            new Sortable.create(blocks[0], {
                draggable: '.block',
                handle: '.sortable-handle',
                group: {put: true},
                onAdd: function(e) {
                    const block = $(e.item);
                    setBlockInputName(block, resourceName, regionName);
                }
            });
        });
        // Handle navigation switch.
        $(`#section-${resourceName}-label`).on('click', function(e) {
            Omeka.closeSidebar($('.sidebar'));
            Omeka.openSidebar($(`#block-selector-${resourceName}`));
        });
    });

    // Handle a block button click.
    $('button.option').on('click', function(e) {
        const thisBlockLayoutButton = $(this);
        const resourceName = thisBlockLayoutButton.data('resourceName');
        const regionName = $(`#region-select-${resourceName}`).val();
        const blockLayoutName = thisBlockLayoutButton.data('blockLayoutName');
        addBlock(resourceName, regionName, blockLayoutName);
    });

    // Handle remove block button click.
    $(document).on('click', '.block-remove', function(e) {
        const thisRemoveIcon = $(this);
        const block = thisRemoveIcon.closest('.block');
        thisRemoveIcon.hide();
        block.addClass('delete');
        block.find('.block-restore').show();
        block.find(':input').prop('disabled', true);
    });

    // Handle restore block button click.
    $(document).on('click', '.block-restore', function(e) {
        const thisRestoreIcon = $(this);
        const block = thisRestoreIcon.closest('.block');
        thisRestoreIcon.hide();
        block.removeClass('delete');
        block.find('.block-remove').show();
        block.find(':input').prop('disabled', false);
    });

});
