$(document).ready(function() {

    /**
     * Sets the color to the sample div if valid.
     *
     * Validation is handled by the "pattern" attribute.
     *
     * @param Object input
     */
    var updateColorSample = function(input) {
        var sampleDiv = input.siblings('.color-picker-sample');
        var color = input.val();
        if ('' !== color && input[0].checkValidity()) {
            sampleDiv.css('background-color', color);
        } else {
            sampleDiv.css('background-color', 'transparent');
        }
    }

    $('.color-picker').each(function() {
        updateColorSample($(this).children('input'));
    });
    $(document).on('keyup', '.color-picker > input', function(e) {
        updateColorSample($(this));
    });
});
