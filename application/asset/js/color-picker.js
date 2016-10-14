$(document).ready(function() {
    $(document).on('keyup', '.color-picker > input', function(e) {
        var thisInput = $(this);
        var sampleDiv = thisInput.siblings('.color-picker-sample');
        var color = thisInput.val();
        if ('' == color) {
            thisInput.css('background-color', '#ffffff');
            sampleDiv.css('background-color', 'transparent');
        } else if (color.match(/^#([0-9a-f]{3}){1,2}$/i) || 'transparent' == color) {
            thisInput.css('background-color', '#ffffff');
            sampleDiv.css('background-color', color);
        } else {
            thisInput.css('background-color', '#f1dada');
            sampleDiv.css('background-color', 'transparent');
        }
    });
});
