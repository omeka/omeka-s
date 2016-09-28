$(document).ready(function() {

var mediaList = $('#media-list');
var index = mediaList.data('mediaCount');

new Sortable(mediaList[0], {
    draggable: ".media",
    handle: ".sortable-handle"
});

$('#media-selector button').click(function () {
    var thisButton = $(this);
    var type = thisButton.data('media-type');
    if (!type) {
        return;
    }

    var template = $('#media-template-' + type).data('template');
    mediaList.append(template.replace(/__index__/g, index++));
    thisButton.val('');
    $('html, body').animate({
        scrollTop: ($('.media-field-wrapper').last().offset().top -100)
    },200);
    $('#media-list .no-resources').hide();
});

$('#item-media').on('click', 'a.remove-new-media-field', function (e) {
    e.preventDefault();
    $(this).parents(".media-field-wrapper").remove();
    if ($('.media-field-wrapper').length < 1) {
        $('#media-list .no-resources').show();
    }
});

});
