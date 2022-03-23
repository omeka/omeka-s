/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */
CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here.
    // For complete reference see:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    let eventData;

    // Configure the toolbar
    eventData = {
        toolbar: [
            {
                name: 'advanced',
                items : [
                    'Sourcedialog',
                    '-',
                    'Link', 'Unlink', 'Anchor',
                    '-',
                    'Format', 'Styles', 'PasteFromWord'
                ]
            },
            '/',
            {
                items: [
                    'Bold', 'Italic', 'Underline', 'Strike',
                    '-',
                    'NumberedList', 'BulletedList', 'Indent', 'Outdent', 'Blockquote',
                ]
            }
        ]
    };
    $(document).trigger('ckeditor:toolbar', eventData);
    config.toolbar = eventData.toolbar;

    // Disable content filtering
    config.allowedContent = true;

    // Add extra plugins
    eventData = {
        extraPlugins: ['sourcedialog']
    };
    $(document).trigger('ckeditor:extra-plugins', eventData);
    config.extraPlugins = eventData.extraPlugins;
};
