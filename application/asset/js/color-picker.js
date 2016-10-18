/**
 * Validate color picker user input
 *
 * Sets the color to the sample div if valid. If invalid, marks the input as
 * invalid. A valid color is a three- or six-digit hexadecimal form, or
 * "transparent".
 *
 * @param Object input
 */
var validateColor = function(input) {
    var sampleDiv = input.siblings('.color-picker-sample');
    var color = input.val();
    if ('' == color) {
        input.css('background-color', '#ffffff');
        sampleDiv.css('background-color', 'transparent');
    } else if (color.match(/^#([0-9a-f]{3}){1,2}$/i) || 'transparent' == color) {
        input.css('background-color', '#ffffff');
        sampleDiv.css('background-color', color);
    } else {
        input.css('background-color', '#f1dada');
        sampleDiv.css('background-color', 'transparent');
    }
}

$(document).ready(function() {
    $('.color-picker').each(function() {
        validateColor($(this).children('input'));
    });
    $(document).on('keyup', '.color-picker > input', function(e) {
        validateColor($(this));
    });
});
