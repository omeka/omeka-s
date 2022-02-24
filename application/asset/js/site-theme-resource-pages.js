$(document).ready(function() {
    const form = $('#resource-page-config-form');
    const resourcePageBlocks = form.data('resourcePageBlocks');
    const resourcePageRegions = form.data('resourcePageRegions');
    const blockLayoutLabels = form.data('blockLayoutLabels');

    // Add a block to a resource page.
    const addBlock = function(blocks, resourceName, regionName, blockLayoutName) {
        const block = $(form.data('blockTemplate'));
        setBlockInputName(block, resourceName, regionName);
        let blockLayoutLabel = blockLayoutLabels[blockLayoutName];
        if (!blockLayoutLabel) {
            blockLayoutLabel = `${Omeka.jsTranslate('Unknown block layout')} [${blockLayoutName}]`;
        }
        block.find('.block-layout-label').text(blockLayoutLabel);
        block.find('.block-layout-name').val(blockLayoutName);
        blocks.append(block);
    };

    // Set the block input name
    const setBlockInputName = function(block, resourceName, regionName) {
        const blockNameInput = block.find('.block-layout-name');
        blockNameInput.attr('name', blockNameInput.data('nameTemplate')
            .replace('__RESOURCE_NAME__', resourceName)
            .replace('__REGION_NAME__', regionName)
        );
    };

    $.each(['items', 'item_sets', 'media'], function(index, resourceName) {
        // Populate the block layout lists on load.
        $.each(resourcePageBlocks[resourceName], function(regionName, blockLayoutNames) {
            const blocks = $(`ul.blocks[data-resource-name="${resourceName}"][data-region-name="${regionName}"]`);
            if (!blocks.length) {
                // There is no corresponding list for this region. Continue to
                // next region.
                return;
            }
            $.each(blockLayoutNames, function(index, blockLayoutName) {
                addBlock(blocks, resourceName, regionName, blockLayoutName);
                $(`button.option[data-resource-name="${resourceName}"][data-block-layout-name="${blockLayoutName}"]`).prop('disabled', true);
            });
        });
        // Make blocks sortable on load.
        $.each(resourcePageRegions[resourceName], function(regionName, regionLabel) {
            const blocks = $(`ul.blocks[data-resource-name="${resourceName}"][data-region-name="${regionName}"]`);
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

    // Handle a block button click.
    $('button.option').on('click', function(e) {
        const thisBlockLayoutButton = $(this);
        const resourceName = thisBlockLayoutButton.data('resourceName');
        const regionName = $(`#region-select-${resourceName}`).val();
        const blockLayoutName = thisBlockLayoutButton.data('blockLayoutName');
        const blocks = $(`ul.blocks[data-resource-name="${resourceName}"][data-region-name="${regionName}"]`);
        addBlock(blocks, resourceName, regionName, blockLayoutName);
        thisBlockLayoutButton.prop('disabled', true);
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
