// @see https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html
CKEDITOR.editorConfig = function(config) {
    // Configure the toolbar
    config.toolbar = [
        {
            name: 'advanced',
            items : ['Sourcedialog', '-', 'Link', 'Unlink', 'Anchor', '-', 'Format', 'Styles', 'PasteFromWord']
        },
        '/',
        {
            items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'NumberedList', 'BulletedList', 'Indent', 'Outdent', 'Blockquote', '-', 'RemoveFormat']
        }
    ];

    config.stylesSet = 'default:../../js/custom-ckeditor-styles.js';
    // Disable content filtering
    config.allowedContent = true;
    // Add extra plugins
    config.extraPlugins = ['sourcedialog','removeformat'];
    // Allow other scripts to modify configuration.
    $(document).trigger('o:ckeditor-config', config);
};
