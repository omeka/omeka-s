CKEDITOR.plugins.add('zotero', {
    icons: 'zotero',
    init: function(editor) {
        editor.addCommand('doZotero', new CKEDITOR.dialogCommand('zoteroDialog'));
        editor.ui.addButton('Zotero', {
            label: 'Zotero',
            command: 'doZotero',
            toolbar: 'insert'
        });
        CKEDITOR.dialog.add('zoteroDialog', this.path + 'dialogs/zotero.js');
    }
});
