CKEditorFootnotes
==================

Maintainers Required
--------------------

Unfortunately I don't have the time to give this project the attention it deserves. I'm happy to hand this over to someone or add contributors to help keep this ticking over.
If you're interested, please get in touch.

---

Footnotes plugin for CKEditor.

Demo: http://demo.gridlight-design.co.uk/ckeditor-footnotes.html

CKEditor Addon: http://ckeditor.com/addon/footnotes

Configuring multiple instances
------------------------------

As of 1.0.5 the plugin accepts a configuration option to allow you to prefix all your footnotes when the editor is instantiated.

E.g.

~~~
CKEDITOR.replace( 'editor1', {
    footnotesPrefix: 'a'
} );
~~~

This could be set dynamically to allow you to ensure that all chunks of text can contain unique ID's, allowing you to include multiple chunks of text on any given page with ID clashes.

For example, it should be possible to use a server-side script to set this variable to the id of a database row.


Other configuration
-------------------

In master, it's now possible to to set configuration for the Footnotes title and the titles elements:

E.g.

~~~
CKEDITOR.replace( 'editor1', {
    footnotesDisableHeader: true, // Defaults to false
    footnotesHeaderEls: ['<p><b>', '</b></p>'], // Defaults to ['<h2>', '</h2>']
    footnotesTitle: 'References', // Defaults to 'Footnotes'
    footnotesDialogEditorExtraConfig: { height: 150 } // Will be merged with the default options for the footnote editor
} );
~~~

Paste From Word
---------------

A complimentary plugin that allows automatic conversion from content pasted from word is now available:
[CKEditorFootnotes-PasteFromWord](https://github.com/andykirk/CKEditorFootnotes-PasteFromWord)
