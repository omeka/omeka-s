$(document).ready(function () {
    // The query's resource type must match the browse's resource type.
    $('#content').on('change', '.browse-preview-resource-type', function(e) {
        const resourceTypeSelect = $(this);
        resourceTypeSelect.closest('.block').find('.query-form-element').data('resourceType', resourceTypeSelect.val());
    });
});
