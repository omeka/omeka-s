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
        var asset = $('.selected-asset');
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
            block.find('a.remove-value, a.restore-value').show();
            $(this).hide();
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
            selectingAttachmentButton.before(newAsset).addClass('asset-selecting-button');
            $('.new.attachment .asset-options-configure').click();
            $('#asset-options .asset-form-select').click();
        });

        $('#content').on('click', '.change-selected-asset', function () {
            var assetSidebar = $('#asset-sidebar');
            var selectingAttachmentButton = $(this);
            Omeka.openSidebar(assetSidebar);
            Omeka.populateSidebarContent(assetSidebar, selectingAttachmentButton.data('sidebar-content-url'));
            if (selectingAttachmentButton.hasClass('add-asset-attachment')) {
                $('.asset-selecting-button').removeClass('asset-selecting-button');
            }
            selectingAttachmentButton.addClass('asset-selecting-button');
        });

        $('#content').on('click', '.asset-list .select-asset', function (e) {
            var assetOptions = $('#asset-options');
            assetOptions.addClass('active');
            assetOptions.find('h3.selected-asset-name').text($(this).find('.asset-name').text());
            if ($('.add-asset-attachment').hasClass('asset-selecting-button')) {
                assetOptions.find('.asset-option').val('');
                resetAssetOption($('#asset-options .page-link'));
            }
        });

        $('#content').on('click', '#asset-options-confirm-panel', function() {
            var selectingAttachment = $('.selecting.attachment');
            selectingAttachment.removeClass('new');
            selectingAttachment.find('input[type="hidden"').removeAttr('disabled');
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
    });
})(window.jQuery);
