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
            items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'NumberedList', 'BulletedList', 'Indent', 'Outdent', 'Blockquote']
        }
    ];
    // Disable content filtering
    config.allowedContent = true;
    // Add extra plugins
    config.extraPlugins = ['sourcedialog'];
    // Allow other scripts to modify configuration.
    $(document).trigger('o:ckeditor-config', config);
};
