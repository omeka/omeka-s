/**
 * Populate a prompt row with the provided data.
 *
 * @param {Object} promptData
 */
var populatePromptRow = function(promptData) {

    // Detect whether a row is currently being edited. If one is, populate that
    // one. If one isn't, create a new row using the row template, populate it,
    // and append it to the prompts table.
    var promptRows = $('#prompts');
    var promptRow = promptRows.children('.prompt-editing');
    if (!promptRow.length) {
        var index = promptRows.children('.prompt').length;
        var promptRowTemplate = $('#prompts').data('promptRowTemplate');
        promptRow = $(promptRowTemplate.replace(/__INDEX__/g, index));
        promptRow.find('.prompt-id').val(promptData['o:id']);
        promptRows.append(promptRow);
    }

    // Populate the visual elements.
    var typeText = $('#prompt-type option[value="' + promptData['o-module-collecting:type'] + '"]').text();
    if ('property' === promptData['o-module-collecting:type']) {
        var propertyText = $('#prompt-property')
            .find('option[value="' + promptData['o:property']['o:id'] + '"]')
            .data('term');
        typeText += ' [' + propertyText + ']';
    } else if ('media' === promptData['o-module-collecting:type']) {
        var mediaTypeText = $('#prompt-media-type')
            .find('option[value="' + promptData['o-module-collecting:media_type'] + '"]')
            .text();
        typeText += ' [' + mediaTypeText + ']';
    }
    promptRow.find('.prompt-type-span').html(typeText);
    promptRow.find('.prompt-text-span').text(promptData['o-module-collecting:text']);

    // Populate the hidden inputs.
    promptRow.find('.prompt-type').val(promptData['o-module-collecting:type']);
    promptRow.find('.prompt-text').val(promptData['o-module-collecting:text']);
    promptRow.find('.prompt-input-type').val(promptData['o-module-collecting:input_type']);
    promptRow.find('.prompt-select-options').val(promptData['o-module-collecting:select_options']);
    promptRow.find('.prompt-resource-query').val(promptData['o-module-collecting:resource_query']);
    promptRow.find('.prompt-custom-vocab').val(promptData['o-module-collecting:custom_vocab']);
    promptRow.find('.prompt-media-type').val(promptData['o-module-collecting:media_type']);
    promptRow.find('.prompt-required').val(promptData['o-module-collecting:required'] ? '1' : '0');
    if (promptData['o:property']) {
        promptRow.find('.prompt-property-id').val(promptData['o:property']['o:id']);
    }
}

/**
 * Reset the sidebar to its default state (i.e. no selected type).
 */
var resetSidebar = function() {
    $('#prompt-type').prop('selectedIndex', 0)
        .prop('disabled', false).css('background-color', '#ffffff');
    var promptText = $('#prompt-text');
    if (promptText.hasClass('html-editor')) {
        promptText.removeClass('html-editor').ckeditor().editor.destroy();
    }
    promptText.val('').closest('.sidebar-section').hide();
    $('#prompt-property').prop('selectedIndex', 0).closest('.sidebar-section').hide();
    $('#prompt-media-type').prop('selectedIndex', 0).closest('.sidebar-section').hide();
    $('#prompt-input-type').prop('selectedIndex', 0).closest('.sidebar-section').hide();
    $('#prompt-select-options').val('').closest('.sidebar-section').hide();
    $('#prompt-resource-query').val('').closest('.sidebar-section').hide();
    $('#prompt-custom-vocab').val('').closest('.sidebar-section').hide();
    $('#prompt-required').prop('checked', false).closest('.sidebar-section').hide();
    $('#prompt-save').hide();

    // The form may only have one "user_name" prompt.
    var hasUserName = $('#prompts .prompt-type[value="user_name"]').length;
    var userNameOption = $('#prompt-type option[value="user_name"]');
    hasUserName ? userNameOption.hide() : userNameOption.show();

    // The form may only have one "user_email" prompt.
    var hasUserEmail = $('#prompts .prompt-type[value="user_email"]').length;
    var userEmailOption = $('#prompt-type option[value="user_email"]');
    hasUserEmail ? userEmailOption.hide() : userEmailOption.show();
}

/**
 * Set the sidebar to the default state of the provided type and show it.
 *
 * @param {String} type
 */
var setSidebarForType = function(type) {
    resetSidebar();
    switch (type) {
        case 'property':
            $('#prompt-property').closest('.sidebar-section').show();
            $('#prompt-input-type').closest('.sidebar-section').show();
            $('#prompt-required').closest('.sidebar-section').show();
            break;
        case 'media':
            $('#prompt-media-type').closest('.sidebar-section').show();
            $('#prompt-required').closest('.sidebar-section').show();
            break;
        case 'user_name':
        case 'user_email':
            $('#prompt-required').closest('.sidebar-section').show();
            break;
        case 'input':
        case 'user_private':
        case 'user_public':
            $('#prompt-input-type').closest('.sidebar-section').show();
            $('#prompt-required').closest('.sidebar-section').show();
            break;
        case 'html':
            $('#prompt-text').addClass('html-editor').ckeditor();
            break;
        default:
            // invalid or no prompt type
            return;
    }
    $('#prompt-type').val(type);
    $('#prompt-text').closest('.sidebar-section').show();
    $('#prompt-save').show();
}

/**
 * Set the custom vocab section of the edit prompt sidebar.
 *
 * @param {Object} prompt
 */
var setCustomVocabSection = function(prompt) {
    var customVocab = prompt.find('.prompt-custom-vocab').val();
    var customVocabSelect = $('#prompt-custom-vocab');
    if (!customVocabSelect.has('option[value="' + customVocab + '"]').length) {
        customVocab = ''; // Custom vocab does not exist
    }
    customVocabSelect.val(customVocab).closest('.sidebar-section').show();
}

$(document).ready(function() {

    $('#prompts-table').hide();

    // Append existing prompts on load. 
    var promptsData = $('#prompts').data('promptsData');
    if (!promptsData.length) {
        // Always add a "dcterms:title" property prompt to a form without
        // prompts. Though not required, we should strongly recommend a title
        // for every collected item.
        promptsData = [{
            'o-module-collecting:type': 'property',
            'o-module-collecting:text': null,
            'o-module-collecting:input_type': 'text',
            'o-module-collecting:select_options': null,
            'o-module-collecting:media_type': null,
            'o-module-collecting:required': true,
            'o:property': {'o:id': $('#prompt-property option[data-term="dcterms:title"]').val()},
        }];
    }
    $.each(promptsData, function() {
        $('#prompts-table').show();
        populatePromptRow(this);
    });

    // Reload the original state of the form to avoid "changes not saved" modal.
    $('#collectingform').trigger('o:form-loaded');

    // Enable prompt sorting.
    new Sortable(document.getElementById('prompts'), {
        handle: '.sortable-handle'
    });

    // Handle changing the prompt's type.
    $('#prompt-type').on('change', function() {
        setSidebarForType($(this).val());
    });

    // Handle changing the prompt's input type.
    $('#prompt-input-type').on('change', function() {
        var inputType = $(this).val();
        var selectOptionsSection = $('#prompt-select-options').closest('.sidebar-section');
        var resourceQuerySection = $('#prompt-resource-query').closest('.sidebar-section');
        var customVocabSection = $('#prompt-custom-vocab').closest('.sidebar-section');
        if ('select' === inputType) {
            selectOptionsSection.show();
        } else {
            selectOptionsSection.hide();
        }
        if ('item' === inputType) {
            resourceQuerySection.show();
        } else {
            resourceQuerySection.hide();
        }
        if ('custom_vocab' === inputType) {
            customVocabSection.show();
        } else {
            customVocabSection.hide();
        }
    });

    // Handle the delete prompt icon.
    $('#prompts').on('click', '.prompt-delete', function(e) {
        e.preventDefault();
        var deleteIcon = $(this);
        var prompt = deleteIcon.closest('.prompt');
        prompt.find(':input').prop('disabled', true);
        prompt.addClass('delete');
        prompt.find('.prompt-undo-delete').show();
        prompt.find('.prompt-edit').hide();
        if (prompt.hasClass('prompt-editing')) {
            Omeka.closeSidebar($('#prompt-sidebar'));
        }
        deleteIcon.hide();
    });

    // Handle the undo delete prompt icon.
    $('#prompts').on('click', '.prompt-undo-delete', function(e) {
        e.preventDefault();
        var undoIcon = $(this);
        var prompt = undoIcon.closest('.prompt');
        prompt.find(':input').prop('disabled', false);
        prompt.removeClass('delete');
        prompt.find('.prompt-delete').show();
        prompt.find('.prompt-edit').show();
        undoIcon.hide();
    });

    // Handle the add prompt button.
    $('#prompt-add').on('click', function(e) {
        e.preventDefault();
        resetSidebar();
        $('#prompts > .prompt').removeClass('prompt-editing');
        Omeka.openSidebar($('#prompt-sidebar'));
    });

    // Handle the edit prompt icon.
    $('#prompts').on('click', '.prompt-edit', function(e) {
        e.preventDefault();

        var prompt = $(this).closest('.prompt');
        var type = prompt.find('.prompt-type').val();
        var text = prompt.find('.prompt-text').val();

        prompt.siblings().removeClass('prompt-editing');
        prompt.addClass('prompt-editing');

        setSidebarForType(type);
        switch (type) {
            case 'property':
                var inputType = prompt.find('.prompt-input-type').val();
                $('#prompt-text').val(text);
                $('#prompt-property').val(prompt.find('.prompt-property-id').val());
                $('#prompt-input-type').val(inputType);
                if ('select' === inputType) {
                    var selectOptions = prompt.find('.prompt-select-options').val();
                    $('#prompt-select-options').val(selectOptions).closest('.sidebar-section').show();
                }
                if ('item' === inputType) {
                    var resourceQuery = prompt.find('.prompt-resource-query').val();
                    $('#prompt-resource-query').val(resourceQuery).closest('.sidebar-section').show();
                }
                if ('custom_vocab' === inputType) {
                    setCustomVocabSection(prompt);
                }
                break;
            case 'media':
                var mediaType = prompt.find('.prompt-media-type').val();
                $('#prompt-text').val(text);
                $('#prompt-media-type').val(mediaType);
                break;
            case 'input':
            case 'user_private':
            case 'user_public':
                var inputType = prompt.find('.prompt-input-type').val();
                $('#prompt-text').val(text);
                $('#prompt-input-type').val(inputType);
                if ('select' === inputType) {
                    var selectOptions = prompt.find('.prompt-select-options').val();
                    $('#prompt-select-options').val(selectOptions).closest('.sidebar-section').show();
                }
                if ('custom_vocab' === inputType) {
                    setCustomVocabSection(prompt);
                }
                break;
            case 'user_name':
            case 'user_email':
            case 'html':
                $('#prompt-text').val(text);
                break;
            default:
                // invalid or no prompt type
                return;
        }

        // A prompt type cannot be changed once it's saved.
        $('#prompt-type').prop('disabled', true).css('background-color', '#dfdfdf');
        $('#prompt-required').prop('checked', '1' === prompt.find('.prompt-required').val() ? true : false);
        Omeka.openSidebar($('#prompt-sidebar'));
    });

    // Handle saving the prompt.
    $('#prompt-save').on('click', function(e) {
        e.preventDefault();

        var promptData = {
            'o-module-collecting:type': $('#prompt-type').val(),
            'o-module-collecting:text': $('#prompt-text').val(),
            'o-module-collecting:input_type': $('#prompt-input-type').val(),
            'o-module-collecting:select_options': $('#prompt-select-options').val(),
            'o-module-collecting:resource_query': $('#prompt-resource-query').val(),
            'o-module-collecting:custom_vocab': $('#prompt-custom-vocab').val(),
            'o-module-collecting:media_type': $('#prompt-media-type').val(),
            'o-module-collecting:required': $('#prompt-required').prop('checked'),
            'o:property': {'o:id': $('#prompt-property').val()},
        };

        // Validate the data before populating the row.
        switch (promptData['o-module-collecting:type']) {
            case 'property':
                if (!$.isNumeric(promptData['o:property']['o:id'])) {
                    alert('You must select a property.');
                    return;
                }
                if (!promptData['o-module-collecting:input_type']) {
                    alert('You must select an input type.');
                    return;
                }
                if ('custom_vocab' === promptData['o-module-collecting:input_type']
                    && !promptData['o-module-collecting:custom_vocab']
                ) {
                    alert('You must select a custom vocab.');
                    return;
                }
                break;
            case 'media':
                if (!promptData['o-module-collecting:text']) {
                    alert('You must provide prompt text.');
                    return;
                }
                if (!promptData['o-module-collecting:media_type']) {
                    alert('You must select a media type.');
                    return;
                }
                break;
            case 'input':
            case 'user_private':
            case 'user_public':
                if (!promptData['o-module-collecting:text']) {
                    alert('You must provide prompt text.');
                    return;
                }
                if (!promptData['o-module-collecting:input_type']) {
                    alert('You must select an input type.');
                    return;
                }
                if ('custom_vocab' === promptData['o-module-collecting:input_type']
                    && !promptData['o-module-collecting:custom_vocab']
                ) {
                    alert('You must select a custom vocab.');
                    return;
                }
                break;
            case 'user_name':
            case 'user_email':
            case 'html':
                if (!promptData['o-module-collecting:text']) {
                    alert('You must provide prompt text.');
                    return;
                }
                break;
            default:
                // invalid or no prompt type
                return;
        }

        populatePromptRow(promptData);
        $('#prompts-table').show();
        Omeka.closeSidebar($('#prompt-sidebar'));
    });

});
