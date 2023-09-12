$(document).ready( function() {

$('#vocabulary-form').on('submit', function(e) {
    const namespaceUriInput = $('#o\\:namespace_uri');
    const namespaceUri = namespaceUriInput.val();
    const originalNamespaceUri = namespaceUriInput.data('originalNamespaceUri');

    // The user must confirm that the namespace URI has changed.
    if (originalNamespaceUri && (namespaceUri !== originalNamespaceUri)) {
        if (!window.confirm(namespaceUriInput.data('confirmChange'))) {
            e.preventDefault();
        }
    }

    // The user must confirm that the namespace URI does not end with / or #.
    if (!(namespaceUri.endsWith('/') || namespaceUri.endsWith('#'))) {
        if (!window.confirm(namespaceUriInput.data('confirmEndsWith'))) {
            e.preventDefault();
        }
    }
});

});
