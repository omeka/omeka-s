/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */
CKEDITOR.editorConfig = function( config ) {
    // Define changes to default configuration here.
    // For complete reference see:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    config.toolbar = [
                      { "name" : "advanced", "items" : ['Source', '-', 'Link', 'Unlink']},
                      "/",
                      { "items" : ['Bold', 'Italic', 'Underline']}
                      ];
};    

