$(document).ready(function() {

const mediaList = $('#media-list');
let index = mediaList.data('mediaCount');

const createMediaFromTemplate = function(type) {
    const mediaTemplate = $('#media-template-' + type)
        .data('template')
        .replace(/__index__/g, index++);
    return $(mediaTemplate);
}

new Sortable(mediaList[0], {
    draggable: '.media',
    handle: '.sortable-handle'
});

$('#media-selector button').on('click', function(e) {
    const thisButton = $(this);
    const type = thisButton.data('media-type');
    mediaList.append(createMediaFromTemplate(type));
    $('html, body').animate({
        scrollTop: ($('.media-field-wrapper').last().offset().top -100)
    }, 200);
    $('#media-list .no-resources').hide();
});

$('#item-media').on('click', 'a.remove-new-media-field', function(e) {
    e.preventDefault();
    $(this).parents(".media-field-wrapper").remove();
    if ($('.media-field-wrapper').length < 1) {
        $('#media-list .no-resources').show();
    }
});

// Handle file selection for upload media.
$(document).on('change', '.media-file-input', function(e) {
    const thisFileInput = $(this);
    // Iterate every file in the file list.
    for (const [fileIndex, file] of Object.entries(this.files)) {
        // Use the DataTransfer API to create a new FileList.
        const list = new DataTransfer();
        list.items.add(file);
        if (0 == fileIndex) {
            // Add the first file to the already opened upload interface.
            this.files = list.files;
        } else {
            // Add each subsequent file to a new upload interface.
            const uploadMedia = createMediaFromTemplate('upload');
            const fileInput = uploadMedia.find('.media-file-input');
            fileInput[0].files = list.files;
            thisFileInput.closest('.media').after(uploadMedia);
        }
    }
});

});
