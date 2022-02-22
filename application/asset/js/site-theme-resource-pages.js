$(document).ready(function() {
    const resourcePageBlocks = $('#resource-page-config-form').data('resourcePageBlocks');
    const blockLayoutLabels = $('#resource-page-config-form').data('blockLayoutLabels');
    // Add a block to a resource page.
    const addBlock = function(blocks, blockLayoutName) {
        const block = $(blocks.data('blockTemplate'));
        let blockLayoutLabel = blockLayoutLabels[blockLayoutName];
        if (!blockLayoutLabel) {
            blockLayoutLabel = `${Omeka.jsTranslate('Unknown block layout')} [${blockLayoutName}]`;
        }
        block.find('.block-layout-label').text(blockLayoutLabel);
        block.find('.block-layout-name').val(blockLayoutName);
        blocks.append(block);
    };
    // Populate the block layout lists on load.
    $.each(resourcePageBlocks['items']['main'], function(key, blockLayoutName) {
        const blocks = $('#item-blocks');
        addBlock(blocks, blockLayoutName);
        $('#item-block-selector').find(`button.option[value="${blockLayoutName}"]`).prop('disabled', true);
    })
    $.each(resourcePageBlocks['media']['main'], function(key, blockLayoutName) {
        const blocks = $('#media-blocks');
        addBlock(blocks, blockLayoutName);
        $('#media-block-selector').find(`button.option[value="${blockLayoutName}"]`).prop('disabled', true);
    })
    $.each(resourcePageBlocks['item_sets']['main'], function(key, blockLayoutName) {
        const blocks = $('#item-set-blocks');
        addBlock(blocks, blockLayoutName);
        $('#item-set-block-selector').find(`button.option[value="${blockLayoutName}"]`).prop('disabled', true);
    })
    // Open the correct sidebar on load.
    switch (window.location.hash.substr(1)) {
        case 'media-section':
            Omeka.openSidebar($('#media-block-selector'));
            break;
        case 'item-set-section':
            Omeka.openSidebar($('#item-set-block-selector'));
            break;
        case 'item-section':
        default:
            Omeka.openSidebar($('#item-block-selector'));
    }
    // Make blocks sortable on load.
    new Sortable(
        document.getElementById('item-blocks'),
        {draggable: '.block', handle: '.sortable-handle'}
    );
    new Sortable(
        document.getElementById('item-set-blocks'),
        {draggable: '.block', handle: '.sortable-handle'}
    );
    new Sortable(
        document.getElementById('media-blocks'),
        {draggable: '.block', handle: '.sortable-handle'}
    );
    // Handle navigation switch.
    $('#item-section-label').on('click', function(e) {
        Omeka.closeSidebar($('.sidebar'));
        Omeka.openSidebar($('#item-block-selector'));
    });
    $('#media-section-label').on('click', function(e) {
        Omeka.closeSidebar($('.sidebar'));
        Omeka.openSidebar($('#media-block-selector'));
    });
    $('#item-set-section-label').on('click', function(e) {
        Omeka.closeSidebar($('.sidebar'));
        Omeka.openSidebar($('#item-set-block-selector'));
    });
    // Handle a block button click.
    $('button.option').on('click', function(e) {
        const thisBlockLayoutButton = $(this);
        const blockLayoutName = thisBlockLayoutButton.val();
        const blocks = $('.section.active .blocks');
        addBlock(blocks, blockLayoutName);
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
