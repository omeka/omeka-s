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
    const thisUploadMedia = thisFileInput.closest('.media');
    const additionalUploadMedia = [];

    // Iterate every file in the FileList.
    for (const [fileIndex, file] of Object.entries(this.files)) {

        let uploadMedia;
        let fileInput;

        // Use the DataTransfer API to create a new FileList containing one
        // file, then set the FileList to this file input or additional file
        // inputs if the original FileList contains more than one file.
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        if (0 == fileIndex) {
            // Add the first file to this file input.
            uploadMedia = thisUploadMedia;
            fileInput = thisFileInput;
        } else {
            // Add each additional file to a new file input.
            uploadMedia = createMediaFromTemplate('upload');
            fileInput = uploadMedia.find('.media-file-input');
            additionalUploadMedia.push(uploadMedia);
        }
        fileInput[0].files = dataTransfer.files;

        // Add a thumbnail when the file is an image.
        uploadMedia.find('.media-file-image').remove();
        if ((/^image\/(png|jpe?g|gif)$/).test(file.type)) {
            const imageSrc = URL.createObjectURL(file);
            const img = new Image();
            img.onload = function() {
                const maxSize = 200;
                const smallestPercent = Math.min(maxSize / this.width, maxSize / this.height);
                img.width = this.width * smallestPercent;
                img.height = this.height * smallestPercent;
                uploadMedia.append(img);
            }
            img.src = imageSrc;
            img.className = 'media-file-image';
        }
    }

    // Append the additional upload interfaces in the order they were added.
    thisUploadMedia.after(additionalUploadMedia);
});

});
