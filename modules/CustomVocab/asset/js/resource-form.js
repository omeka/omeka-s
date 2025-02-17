// Add Chosen UI to Custom Vocab selects.
$(document).on('o:prepare-value', function(e, type, value) {
    if (!type.startsWith('customvocab:')) {
        return;
    }
    value.find('select').chosen({
        width: '100%',
        disable_search_threshold: 25,
        allow_single_deselect: true,
        // // More than 1000 may cause performance issues
        // @see https://github.com/harvesthq/chosen/issues/2580
        max_shown_results: 1000,
    });
});
