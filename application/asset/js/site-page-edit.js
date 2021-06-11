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

    /**
     * Add a asset attachment.
     *
     * @param object selectingAttachment Add the item to this attachment
     * @param object itemData The data of the item to add
     */
     function addAssetAttachment(selectingAttachmentButton)
     {
        var attachment = $(selectingAttachmentButton.parents('.attachments').data('template'));
        populateAssetAttachment(attachment);
        selectingAttachmentButton.before(attachment);
     }

     function populateAssetAttachment(attachment) {
        var asset = $('.selected-asset');
        var assetImage = asset.find('img').clone().attr('class', '');
        var assetTitle = asset.find('.selected-asset-name').text();
        var assetId = asset.find('.selected-asset-id').val();
        var pageInput =  attachment.find('input.page');
        attachment.find('.asset-title').empty().append(assetTitle).prepend($('<div class="thumbnail"></div>'));
        attachment.find('.thumbnail').append(assetImage);
        attachment.find('input.asset').val(assetId);
        pageInput.val($('#selected-page-id').val()).data('page-title', $('.selected-page').text());
     }

     function selectPageLink(pageButton) {
         $('.selected-page').text(pageButton.text());
         $('#selected-page-id').val(pageButton.val());
     }

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
            var sidebar = $('#asset-options');
            var selectingAttachment = $(this).closest('.attachment');
            var assetInput = selectingAttachment.find('input.asset');
            $('.asset-selecting-button').removeClass('asset-selecting-button');
            $(this).addClass('asset-button-selecting');
            $('.selecting.attachment').removeClass('selecting');
            selectingAttachment.addClass('selecting');

            var currentAsset = selectingAttachment.find('.thumbnail img');
            var newSelectedAsset = currentAsset.clone().addClass('selected-asset-image');
            var assetOptionImage = $('#asset-options .selected-asset-image');
            var pageInput = selectingAttachment.find('input.page');
            assetOptionImage.replaceWith(newSelectedAsset);
            $('#asset-options .selected-asset-id').val(assetInput.val());
            $('#asset-options .selected-asset-name').text(currentAsset.attr('alt'));
            $('#selected-page-id').val(pageInput.val());
            var pageTitle = pageInput.data('page-title');
            if (pageInput.data('page-title') == '') {
                $('.none-selected').removeClass('inactive');
            } else {
                $('.none-selected').addClass('inactive');
            }
            $('.selected-page').text(pageTitle);
            Omeka.openSidebar(sidebar);
        });


        $('#content').on('click', '.add-asset-attachment, .change-selected-asset', function () {
            var assetSidebar = $('#asset-sidebar');
            Omeka.openSidebar(assetSidebar);
            Omeka.populateSidebarContent(assetSidebar, $(this).data('sidebar-content-url'));
            $(this).addClass('asset-selecting-button');
        });

        $('#content').on('click', '#asset-options-confirm-panel', function() {
            var assetSelectingButton = $('.asset-selecting-button');
            if ($('.asset-selecting-button').hasClass('add-asset-attachment')) {
                addAssetAttachment(assetSelectingButton);
            } else {
                var selectingAttachment = $('.selecting.attachment');
                populateAssetAttachment(selectingAttachment);
            }
            Omeka.closeSidebar($('#asset-options'));
            $('.selecting.attachment').removeClass('selecting');
        });

        $('#content').on('click', '.page-select', function() {
            var sidebar = $('#page-list');
            var pageList = $('#page-list .pages');
            var optionTemplate = $('#page-list .option.template');

            Omeka.openSidebar(sidebar);
            var apiUrl = sidebar.data('api-url');
            if (pageList.find('.option').length == 1) {
                $.get(apiUrl, function(data) {
                    data['o:page'].forEach(function(page) {
                        var newButton = optionTemplate.clone();
                        $.get(page['@id'], function(pageData) {
                            newButton.text(pageData['o:title']).val(pageData['o:id']);
                        });
                        pageList.append(newButton);
                        newButton.removeClass('template');
                    });
                }).done(function() {
                    // Update attachment options sidebar after selecting item.
                    pageList.on('click', 'button.option', function(e) {
                        Omeka.closeSidebar($('#page-list'));
                        selectPageLink($(this));
                        $('.none-selected').addClass('inactive');
                    });
                });
            }
        });
    });
})(window.jQuery);