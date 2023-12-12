(function ($) {
    function wysiwyg(context) {
        var config = {
            toolbar:
            [
                ['Sourcedialog', 'Bold', 'Italic', 'Underline', 'Link', 'Unlink', 'PasteFromWord'],
            ],
            height: '96px'
        };

        context.find('.wysiwyg').each(function () {
            var editor = null;
            if ($(this).is('.caption')) {
                editor = CKEDITOR.inline(this, config)
            } else {
                editor = CKEDITOR.inline(this);
            }
            $(this).data('ckeditorInstance', editor);
        })
    }

    /**
     * Open the attachment options sidebar.
     *
     * @param int itemId The attached item ID, if any
     * @param int mediaId The attached media ID, if any
     * @param str caption The attachment caption, if any
     */
    function openAttachmentOptions(itemId, mediaId, caption)
    {
        var attachmentItem = $('#attachment-item');

        // Explicitly reset selected item (setting an undefined "new" item ID will actually leave
        // the old value unchanged).
        attachmentItem.removeData('itemId');
        attachmentItem.data('itemId', itemId);
        return $.post(
            $('#attachment-options').data('url'),
            {itemId: itemId, mediaId: mediaId}
        ).done(function(data) {
            attachmentItem.html(data);
            $('#attachment-caption .caption').val(caption);
            var sidebar = $('#attachment-options');
            Omeka.populateSidebarContent(sidebar, $(this).data('sidebar-content-url'));
            Omeka.openSidebar(sidebar);
            sidebar.scrollTop(0);
        });
    }

    /**
     * Set the selecting attachment.
     *
     * @param object attachment The selecting attachment element
     */
    function setSelectingAttachment(attachment)
    {
        $('.selecting-attachment').removeClass('selecting-attachment');
        attachment.addClass('selecting-attachment');
    }

    function replaceIndex(context, find, index) {
        context.find(':input').each(function() {
            var thisInput = $(this);
            if ($(this).attr('name') == undefined) {
                return;
            }
            var name = thisInput.attr('name').replace('[__' + find + '__]', '[' + index + ']');
            var label = thisInput.parents('.field').find('label').first();
            thisInput.attr('name', name);
            if (!thisInput.is(':hidden')) {
                thisInput.attr('id', name);
            }
            label.attr('for', name);
        });
        context.find('.attachments').each(function () {
            var thisAttachments = $(this);
            var template = thisAttachments.data('template').replace(new RegExp('\\[__' + find + '__\\]', 'g'), '[' + index + ']');
            thisAttachments.data('template', template);
        });
    }

    /**
     * Add an item attachment.
     *
     * Typically used when skipping attachment options.
     *
     * @param object selectingAttachment Add the item to this attachment
     * @param object itemData The data of the item to add
     */
    function addItemAttachment(selectingAttachment, itemData)
    {
        var attachment = $(selectingAttachment.parents('.attachments').data('template'));

        var title = itemData.display_title;
        var thumbnailUrl = itemData.thumbnail_url;
        var thumbnail;
        if (thumbnailUrl) {
            thumbnail = $('<img>', {src: thumbnailUrl});
        }
        attachment.find('input.item').val(itemData.value_resource_id);
        attachment.find('.item-title').empty().append(thumbnail).append(title);
        selectingAttachment.before(attachment);

        if (selectingAttachment.closest('.attachments-form').hasClass('attachments-item-only')) {
            attachment.find('.attachment-options-icon').closest('li').remove();
        }
    }

     function populateAssetAttachment(attachment) {
        var asset = $('#asset-options .selected-asset');
        var assetTitle = asset.find('.selected-asset-name').html();
        var assetImage = asset.find('img').clone().attr('class', '');
        var assetId = asset.find('.selected-asset-id').val();
        if (assetTitle !== '') {
            attachment.find('.asset-title').empty().append(assetTitle).prepend($('<div class="thumbnail"></div>'));
            attachment.find('.thumbnail').append(assetImage);
            attachment.find('input.asset').val(assetId);
        }

        var pageInput =  attachment.find('input.asset-page-id');
        pageInput.attr('data-page-title', $('.selected-page').text()).attr('data-page-url', $('.selected-page + a').attr('href'));

        $('#asset-options .asset-option').each(function() {
            var assetOption = $(this);
            var optionName = assetOption.attr('name');
            attachment.find('.' + optionName).val(assetOption.val());
        });
     }

     function resetAssetOption(optionSelector) {
         var template = $(optionSelector).data('default-html');
        $(optionSelector).find('.asset-option-selection').html(template);
     }

     function selectPageLink(pageButton) {
         var pageUrl = $('.page-status').data('site-url') + '/page/' + pageButton.data('page-slug');
         $('.selected-page').text(pageButton.text());
         $('#asset-page-id').val(pageButton.val());
         $('.selected-page + a').attr('href', pageUrl);
     }

    // Prevent drop into ckeditors to avoid picking up block content when sorting
    CKEDITOR.on('instanceReady', function(e) {
        var editor = e.editor;
        editor.editable().attachListener(editor.container, 'drop', function (dropEv) {
            dropEv.data.preventDefault();
        })
    });

    $(document).ready(function () {
        var list = document.getElementById('blocks');
        var blockIndex = 0;
        new Sortable(list, {
            draggable: ".block",
            handle: ".sortable-handle",
            onStart: function (e) {
                var editor = $(e.item).find('.wysiwyg').ckeditor().editor;
                if (editor) {
                    editor.destroy();
                }
            },
            onEnd: function (e) {
                wysiwyg($(e.item));
            },
        });

        $('#new-block button').click(function() {
            $.post(
                $(this).parents('#new-block').data('url'),
                {layout: $(this).val()}
            ).done(function(data) {
                var newBlock = $(data).appendTo('#blocks');
                newBlock.trigger('o:block-added');
                Omeka.scrollTo(newBlock);
            });
        });

        $('#blocks .block').each(function () {
            $(this).data('blockIndex', blockIndex);
            replaceIndex($(this), 'blockIndex', blockIndex);
            blockIndex++;
        });
        $('#blocks').on('o:block-added', '.block', function () {
            $(this).data('blockIndex', blockIndex);
            replaceIndex($(this), 'blockIndex', blockIndex);
            wysiwyg($(this));
            blockIndex++;
        });
        wysiwyg($('body'));

        $('#blocks').on('click', 'a.remove-value, a.restore-value', function (e) {
            e.preventDefault();
            var block = $(this).parents('.block');
            block.toggleClass('delete');
            block.find('a.remove-value, a.restore-value').removeClass('inactive');
            $(this).toggleClass('inactive');
            Omeka.markDirty($(this).closest('form'));
        });

        $('form').submit(function(e) {
            $('#blocks .block').each(function(blockIndex) {
                var thisBlock = $(this);
                if (thisBlock.hasClass('delete')) {
                    thisBlock.find(':input').prop('disabled', true);
                } else {
                    thisBlock.find('.attachments .attachment').each(function(attachmentIndex) {
                        var thisAttachment = $(this);
                        replaceIndex(thisAttachment, 'attachmentIndex', attachmentIndex);
                    });
                }
                // Set block layout data to form.
                const blockLayoutData = thisBlock.data('block-layout-data');
                blockLayoutData.grid_column_position = thisBlock.find('.block-page-layout-grid-column-position-select').val();
                blockLayoutData.grid_column_span = thisBlock.find('.block-page-layout-grid-column-span-select').val();
                thisBlock.find('.block-layout-data').val(JSON.stringify(blockLayoutData));
            });
        });

        // Toggle attachment status
        $('#blocks').on('click', '.delete,.undo', function(e) {
            e.preventDefault();
            var attachment = $(this).parents('.attachment');
            attachment.toggleClass('delete');
            if (attachment.hasClass('delete')) {
                attachment.find('input[type="hidden"]').each(function() {
                    $(this).attr('disabled', 'disabled');
                });
            } else {
                attachment.find('input[type="hidden"]').each(function() {
                    $(this).removeAttr('disabled');
                });
            }
        });

        $('.collapse-all').on('click', function() {
            $('.block-header.collapse .collapse').click();
        });

        $('.expand-all').on('click', function() {
            $('.block-header:not(.collapse) .expand').click();
        });

        // Toggle block visibility
        $('#blocks').on('click', '.expand,.collapse', function() {
            var blockToggle = $(this);
            blockToggle.parents('.block-header').toggleClass('collapse');
        });

        // Make attachments sortable.
        $('#blocks').on('o:block-added', '.block', function () {
            $(this).find('.attachments').each(function () {
                new Sortable(this, {
                    draggable: ".attachment",
                    handle: ".sortable-handle"
                });
            });
        });
        $('.attachments').each(function() {
            new Sortable(this, {
                draggable: ".attachment",
                handle: ".sortable-handle"
            });
        });

        // Append attachment.
        $('#blocks').on('click', '.attachment-add', function(e) {
            setSelectingAttachment($(this));
            openAttachmentOptions().done(function () {
                $('#attachment-item-select').click();
            });
        });

        // Open attachment options sidebar after selecting attachment.
        $('body').on('click', '.attachment-options-icon', function(e) {
            e.preventDefault();
            var attachment = $(this).closest('.attachment');
            setSelectingAttachment(attachment);
            openAttachmentOptions(
                attachment.find('input.item').val(),
                attachment.find('input.media').val(),
                attachment.find('input.caption').val()
            );
        });

        // Enable item selection for attachments.
        $('#content').on('click', '#attachment-item-select', function(e) {
            e.preventDefault();
            var sidebar = $('#select-resource');
            var sidebarContentUrl = $(this).data('sidebar-content-url')
            var attachmentsForm = $('.selecting-attachment').closest('.attachments-form');
            if (attachmentsForm.data('itemQuery')) {
                sidebarContentUrl = sidebarContentUrl + '?' + $.param(attachmentsForm.data('itemQuery'))
            }
            Omeka.populateSidebarContent(sidebar, sidebarContentUrl);
            Omeka.openSidebar(sidebar);
        });

        // Update attachment options sidebar after selecting item.
        $('#select-resource').on('o:resource-selected', '.select-resource', function(e) {
            var thisSelectResource = $(this);
            var resource = thisSelectResource.closest('.resource').data('resource-values');
            var selectingAttachment = $('.selecting-attachment');

            if (selectingAttachment.closest('.attachments-form').hasClass('attachments-item-only')) {
                // This is an item-only attachment form.
                Omeka.closeSidebar($('#attachment-options'));
                addItemAttachment(selectingAttachment, resource);
            } else {
                // This is a normal attachment form.
                openAttachmentOptions(resource.value_resource_id);
                $('#select-resource').removeClass('active');
            }
        });

        // Add multiple item attachments.
        $('#select-resource').on('o:resources-selected', '.select-resources-button', function(e) {
            Omeka.closeSidebar($('#attachment-options'));
            var selectingAttachment = $('.selecting-attachment');
            $('#item-results').find('.resource')
                .has('input.select-resource-checkbox:checked').each(function() {
                    addItemAttachment(selectingAttachment, $(this).data('resource-values'));
                });
        });

        // Change attached media.
        $('#attachment-item').on('click', 'li.media', function(e) {
            var media = $(this);
            var attachmentItem = $('#attachment-item');

            attachmentItem.find('li.media').removeClass('attached');
            media.addClass('attached');
            attachmentItem.find('img.item-thumbnail').attr('src', media.find('img.media-thumbnail').attr('src'));
            attachmentItem.find('span.media-title').text(media.find('img.media-thumbnail').attr('title'));
        });

        // Apply changes to the attachments form.
        $('#attachment-confirm-panel button').on('click', function(e) {
            e.preventDefault();
            $('#attachment-options').removeClass('active');
            var item = $('#attachment-item');
            var caption = $('#attachment-caption .caption').val();
            var attachment = $('.selecting-attachment');
            if (attachment.hasClass('attachment-add')) {
                var attachments = attachment.parents('.attachments');
                attachment = $(attachments.data('template'));
                $('.selecting-attachment').before(attachment);
            }

            // Set hidden data.
            attachment.find('input.item').val(item.data('itemId'));
            attachment.find('input.media').val(item.find('li.media.attached').data('mediaId'));
            attachment.find('input.caption').val(caption);

            // Set visual elements.
            var title = item.find('.item-title').html();
            if (title) {
                var thumbnail;
                var thumbnailUrl = item.find('.item-thumbnail').attr('src');
                if (thumbnailUrl) {
                    thumbnail = $('<img>', {src: thumbnailUrl});
                }
                attachment.find('.item-title').empty().append(thumbnail).append(title);
            }
        });

        $('#blocks').on('click', '.asset-options-configure', function(e) {
            e.preventDefault();
            Omeka.closeSidebar($('.sidebar.active:not(#new-block)'));
            var selectingAttachment = $(this).closest('.attachment');
            var assetInput = selectingAttachment.find('input.asset');
            $('.asset-selecting-button').removeClass('asset-selecting-button');
            $(this).addClass('asset-selecting-button');
            $('.selecting.attachment').removeClass('selecting');
            selectingAttachment.addClass('selecting');

            var currentAsset = selectingAttachment.find('.thumbnail img');
            if (currentAsset.length > 0) {
                var newSelectedAsset = currentAsset.clone().addClass('selected-asset-image');
                $('#asset-options .selected-asset-name').text(selectingAttachment.find('.asset-title').text());
                $('#asset-options .selected-asset-image').replaceWith(newSelectedAsset);
            } else {
                resetAssetOption('#asset-options .asset-form-element');
            }
            $('#asset-options .selected-asset-id').val(assetInput.val());

            var pageInput = selectingAttachment.find('input.asset-page-id');
            $('#asset-page-id').val(pageInput.val());
            $('.selected-page').text(pageInput.attr('data-page-title'));
            $('.selected-page + a').attr('href', pageInput.attr('data-page-url'));

            $('#asset-options .asset-option').each(function() {
                var assetOption = $(this);
                var optionName = assetOption.attr('name');
                assetOption.val(selectingAttachment.find('.' + optionName).val());
            });
            Omeka.openSidebar($('#asset-options'));
        });

        $('#content').on('click', '.add-asset-attachment', function() {
            var selectingAttachmentButton = $(this);
            var newAsset = selectingAttachmentButton.parents('.attachments').data('template');
            selectingAttachmentButton.before(newAsset);
            $('.new.attachment .asset-options-configure').click();
            $('#asset-options .asset-form-select').click();
        });

        $('#content').on('click', '#asset-options-confirm-panel', function() {
            var selectingAttachment = $('.selecting.attachment');
            selectingAttachment.removeClass('new');
            selectingAttachment.find('input[type="hidden"]').removeAttr('disabled');
            populateAssetAttachment(selectingAttachment);
            Omeka.closeSidebar($('#asset-options'));
            $('.selecting.attachment').removeClass('selecting');
        });

        $('#content').on('click', '#asset-options .sidebar-close', function() {
            $('.new.attachment').remove();
        });

        $('#content').on('click', '.page-select', function() {
            var sidebar = $('#page-list');
            var pageList = $('#page-list .pages');
            Omeka.openSidebar(sidebar);

            pageList.on('click', 'button.option', function(e) {
                Omeka.closeSidebar(sidebar);
                selectPageLink($(this));
            });

        });

        $('#content').on('click', '.page-clear', function() {
            resetAssetOption('#asset-options .page-link');
        });

        // Prepare page layout for use.
        const preparePageLayout = function() {
            const layoutSelect = $('#page-layout-select');
            const gridColumnsSelect = $('#page-layout-grid-columns-select');
            const gridColumnGapInput = $('#page-layout-grid-column-gap-input');
            const gridRowGapInput = $('#page-layout-grid-row-gap-input');
            const gridPreview = $('#preview-page-layout-grid');
            const blockGridControls = $('.block-page-layout-grid-controls');
            const blockGridPreview = $('.preview-block-page-layout-grid');
            // Disable and hide all layout-specific controls by default.
            gridColumnsSelect.hide();
            gridColumnGapInput.closest('.field').hide();
            gridRowGapInput.closest('.field').hide();
            gridPreview.hide();
            blockGridControls.hide();
            blockGridPreview.hide();
            switch (layoutSelect.val()) {
                case 'grid':
                    // Prepare grid layout.
                    gridColumnsSelect.show();
                    gridColumnGapInput.closest('.field').show();
                    gridRowGapInput.closest('.field').show();
                    gridPreview.show();
                    blockGridControls.show();
                    blockGridPreview.show();
                    preparePageGridLayout();
                    break;
                case '':
                default:
                    // Prepare normal flow layout. Do nothing.
                    break;
            }
        };

        // Prepare page grid layout for use.
        const preparePageGridLayout = function() {
            const gridColumns = parseInt($('#page-layout-grid-columns-select').val(), 10);
            $('.block').each(function() {
                const thisBlock = $(this);
                const gridColumnPositionSelect = thisBlock.find('.block-page-layout-grid-column-position-select');
                const gridColumnPositionSelectValue = parseInt(gridColumnPositionSelect.val(), 10);
                const gridColumnSpanSelect = thisBlock.find('.block-page-layout-grid-column-span-select');
                const gridColumnSpanSelectValue = parseInt(gridColumnSpanSelect.val(), 10);
                // Hide invalid positions according to the column # and span #.
                gridColumnPositionSelect.find('option').show()
                    .filter(function() {
                        const thisOption = $(this);
                        const thisValue = parseInt(thisOption.attr('value'), 10);
                        return (thisValue > gridColumns) || (thisValue > (1 + gridColumns - gridColumnSpanSelectValue));
                    }).hide();
                // Hide invalid spans according to the column # and position #.
                gridColumnSpanSelect.find('option').show()
                    .filter(function() {
                        const thisOption = $(this);
                        const thisValue = parseInt(thisOption.attr('value'), 10);
                        return (thisValue > gridColumns) || (thisValue > (1 + gridColumns - gridColumnPositionSelectValue));
                    }).hide();
            });
        };

        // Set layout-specific block controls to their default values.
        const setPageLayoutBlockDefaults = function(blockElements) {
            const layoutSelect = $('#page-layout-select');
            switch (layoutSelect.val()) {
                case 'grid':
                    // Set grid layout defaults.
                    const gridColumns = $('#page-layout-grid-columns-select').val();
                    blockElements.each(function() {
                        const thisBlock = $(this);
                        thisBlock.find('.block-page-layout-grid-column-position-select').val('auto');
                        thisBlock.find('.block-page-layout-grid-column-span-select').val(gridColumns);
                    });
                    break;
                case '':
                default:
                    // Set normal flow layout defaults. Do nothing.
                    break;
            }
        }

        // Preview the page layout grid.
        const previewPageLayoutGrid = function() {
            const previewDiv = $('#grid-layout-preview');
            const gridColumns = parseInt($('#page-layout-grid-columns-select').val(), 10);
            previewDiv.css('grid-template-columns', `repeat(${gridColumns}, 1fr)`).empty();
            let inBlockGroup = false;
            let blockGroupSpan;
            let blockGroupCurrentSpan;
            let blockGroupDiv;
            $('.block').each(function() {
                const thisBlock = $(this);
                const blockLayout = thisBlock.data('block-layout');
                if ('blockGroup' == blockLayout) {
                    // The blockGroup block gets special treatment.
                    if (inBlockGroup) {
                        // Blocks may not overlap.
                        previewDiv.append(blockGroupDiv);
                    }
                    inBlockGroup = true;
                    blockGroupSpan = parseInt(thisBlock.find('.block-group-span').val(), 10);
                    blockGroupCurrentSpan = 0;
                    blockGroupDiv = $(`<div style="display: grid; grid-template-columns: repeat(${gridColumns}, 1fr); grid-column: span ${gridColumns};">`);
                } else {
                    const positionSelect = thisBlock.find('.block-page-layout-grid-column-position-select');
                    const positionSelectValue = parseInt(positionSelect.val(), 10) || 'auto';
                    const spanSelect = thisBlock.find('.block-page-layout-grid-column-span-select');
                    const spanSelectValue = parseInt(spanSelect.val(), 10);
                    const selectedTooltip = $('<div class="selected-tooltip" title="Selected">');
                    const blockDiv = $('<div class="grid-layout-previewing-block">')
                        .css('grid-column', `${positionSelectValue} / span ${spanSelectValue}`);
                    if (thisBlock.hasClass('grid-layout-previewing')) {
                        blockDiv.addClass('grid-layout-previewing').append(selectedTooltip);
                    }
                    blockDiv.hover(
                        function() {
                            thisBlock.addClass('hovered-block');
                            $(this).addClass('hovered-block');
                        },
                        function() {
                            thisBlock.removeClass('hovered-block');
                            $(this).removeClass('hovered-block');
                        }
                    );
                    inBlockGroup
                        ? blockGroupDiv.append(blockDiv)
                        : previewDiv.append(blockDiv);
                }
                if (inBlockGroup) {
                    // The blockGroup block gets special treatment.
                    if (blockGroupCurrentSpan == blockGroupSpan) {
                        previewDiv.append(blockGroupDiv);
                        inBlockGroup = false;
                    } else {
                        blockGroupCurrentSpan++;
                    }
                }
            });
            if (inBlockGroup) {
                // Close the blockGroup block if not already closed.
                previewDiv.append(blockGroupDiv);
            }
            Omeka.openSidebar($('#grid-layout-preview-sidebar'));
        };

        // Prepare page layout on initial load.
        preparePageLayout();

        // Handle adding a block.
        $('#blocks').on('o:block-added', '.block', function(e) {
            const thisBlock = $(this);
            setPageLayoutBlockDefaults(thisBlock);
            preparePageLayout();
        });

        // Handle a page layout change.
        $('#page-layout-select').on('change', function() {
            // Revert to the previous grid state, if any.
            const columnsSelect = $('#page-layout-grid-columns-select');
            columnsSelect.val(columnsSelect.data('page-layout-grid-columns'));
            $('.block').each(function() {
                const thisBlock = $(this);
                const positionSelect = thisBlock.find('.block-page-layout-grid-column-position-select');
                const spanSelect = thisBlock.find('.block-page-layout-grid-column-span-select');
                positionSelect.val(positionSelect.data('block-page-layout-grid-column-position'));
                spanSelect.val(spanSelect.data('block-page-layout-grid-column-span'));
            });
            preparePageLayout();
            $('#page-layout-restore').show();
        });

        // Handle a grid columns change.
        $('#page-layout-grid-columns-select').on('change', function() {
            setPageLayoutBlockDefaults($('.block'));
            preparePageGridLayout();
            $('#page-layout-restore').show();
        });

        // Handle a grid position and grid span change.
        $('#blocks').on('change', '.block-page-layout-grid-column-position-select, .block-page-layout-grid-column-span-select', function() {
            preparePageGridLayout();
            $('#page-layout-restore').show();
        });

        // Handle a page layout grid preview click.
        $('#preview-page-layout-grid').on('click', function(e) {
            e.preventDefault();
            $('.block').removeClass('grid-layout-previewing');
            previewPageLayoutGrid();
        })

        $('#configure-page-layout-data').on('click', function(e) {
            e.preventDefault();
            const pageLayoutDataSidebar = $('#page-layout-data-sidebar');
            Omeka.openSidebar(pageLayoutDataSidebar);
        });

        // Handle a page layout grid preview click for a specific block.
        $('#blocks').on('click', '.preview-block-page-layout-grid', function(e) {
            e.preventDefault();
            $('.block').removeClass('grid-layout-previewing');
            $(this).closest('.block').addClass('grid-layout-previewing');
            previewPageLayoutGrid();
        });

        // Handle closing a page layout grid preview.
        $('#grid-layout-preview-sidebar').on('o:sidebar-closed', function(e) {
            $('.block').removeClass('grid-layout-previewing');
        });

        // Handle a configure block layout click. (open the sidebar)
        $('#blocks').on('click', '.configure-block-layout-data', function(e) {
            e.preventDefault();
            const thisBlock = $(this).closest('.block');
            const blockLayout = thisBlock.data('block-layout');
            const blockLayoutData = thisBlock.data('block-layout-data');
            const blockLayoutDataSidebar = $('#block-layout-data-sidebar');
            $('.block').removeClass('block-layout-data-configuring');
            thisBlock.addClass('block-layout-data-configuring');

            // Prepare form elements that need special handling.
            const templateNameInput = $('#block-layout-data-template-name');
            const blockTemplates = templateNameInput.data('block-templates');
            let templateName = '';
            if (blockTemplates[blockLayout] && blockTemplates[blockLayout][blockLayoutData.template_name]) {
                // Verify that the current theme provides this template.
                templateName = blockLayoutData.template_name;
            }
            templateNameInput.empty()
                .append(templateNameInput.data('empty-option'))
                .append(templateNameInput.data('value-options')[blockLayout]);
            if (blockLayoutData.background_image_asset) {
                const apiEndpointUrl = blockLayoutDataSidebar.data('api-endpoint-url');
                const assetId = parseInt(blockLayoutData.background_image_asset, 10);
                $.get(`${apiEndpointUrl}/assets/${assetId}`, function(data) {
                    blockLayoutDataSidebar.find('.selected-asset').show();
                    blockLayoutDataSidebar.find('img.selected-asset-image').attr('src', data['o:asset_url']);
                    blockLayoutDataSidebar.find('selected-asset-name').attr('src', data['o:name']);
                    blockLayoutDataSidebar.find('.no-selected-asset').hide();
                    blockLayoutDataSidebar.find('.asset-form-clear').show();
                });
            } else {
                blockLayoutDataSidebar.find('.selected-asset').hide();
                blockLayoutDataSidebar.find('.no-selected-asset').show();
                blockLayoutDataSidebar.find('.asset-form-clear').hide();
            }

            // Automatically populate block layout data for inputs with a data-key attribute.
            blockLayoutDataSidebar.find(':input[data-key]').each(function() {
                const thisInput = $(this);
                const key = thisInput.data('key');
                thisInput.val(blockLayoutData[key]);
            });

            // Allow special handling of block layout data.
            $(document).trigger('o:prepare-block-layout-data', [thisBlock]);

            Omeka.openSidebar(blockLayoutDataSidebar);
        });

        // Handle a configure block layout apply changes click (close the sidebar).
        $('#apply-block-layout-data').on('click', function(e) {
            e.preventDefault();
            const block = $('.block-layout-data-configuring');
            const blockLayoutData = block.data('block-layout-data');

            // Automatically apply block layout data for inputs with a data-key attribute.
            $('#block-layout-data-sidebar').find(':input[data-key]').each(function() {
                const thisInput = $(this);
                const key = thisInput.data('key');
                blockLayoutData[key] = thisInput.val();
            });

            // Allow special handling of block layout data.
            $(document).trigger('o:apply-block-layout-data', [block]);

            Omeka.closeSidebar($('#block-layout-data-sidebar'));
        });

        // Handle a layout restore click.
        $('#page-layout-restore').on('click', function() {
            const restoreButton = $(this);
            const layoutSelect = $('#page-layout-select');
            layoutSelect.val(layoutSelect.data('page-layout'));
            preparePageLayout();
            switch (layoutSelect.val()) {
                case 'grid':
                    // Restore grid layout.
                    const gridColumnsSelect = $('#page-layout-grid-columns-select');
                    gridColumnsSelect.val(gridColumnsSelect.data('page-layout-grid-columns'));
                    $('.block').each(function() {
                        const thisBlock = $(this);
                        const gridColumnPositionSelect = thisBlock.find('.block-page-layout-grid-column-position-select');
                        const gridColumnSpanSelect = thisBlock.find('.block-page-layout-grid-column-span-select');
                        let originalGridColumnPosition = gridColumnPositionSelect.data('block-page-layout-grid-column-position');
                        let originalGridColumnSpan = gridColumnSpanSelect.data('block-page-layout-grid-column-span');
                        // Set default values if this is a new block.
                        if ('' === originalGridColumnPosition) {
                            originalGridColumnPosition = 'auto';
                        }
                        if ('' === originalGridColumnSpan) {
                            originalGridColumnSpan = $('#page-layout-grid-columns-select').val();
                        }
                        gridColumnPositionSelect.val(originalGridColumnPosition);
                        gridColumnSpanSelect.val(originalGridColumnSpan);
                    });
                    preparePageGridLayout();
                    break;
                case '':
                default:
                    // Restore normal flow layout. Do nothing.
                    break;
            }
            restoreButton.hide();
        });
    });
})(window.jQuery);
