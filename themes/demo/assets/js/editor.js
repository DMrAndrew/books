
function initEditor(inputName, options = []) {

    let {toolbarItems = []} = options
    CKEDITOR.ClassicEditor.create(document.querySelector(`#${inputName}`), {
        language: 'ru',

        toolbar: {
            items: ['undo', 'redo',
                'heading', '|',
                '|', 'bold', 'italic','strikethrough', 'underline',
                '|', 'numberedList', 'bulletedList',
                '|', 'link', 'blockQuote',].concat(toolbarItems)
        },
        removePlugins: [
            // These two are commercial, but you can try them out without registering to a trial.
            'ExportPdf',
            'ExportWord',
            'CKBox',
            'CKFinder',
            'EasyImage',
            // This sample uses the Base64UploadAdapter to handle image uploads as it requires no configuration.
            // https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/base64-upload-adapter.html
            // Storing images as Base64 is usually a very bad idea.
            // Replace it on production website with other solutions:
            // https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/image-upload.html
             //'Base64UploadAdapter',
            'RealTimeCollaborativeComments',
            'RealTimeCollaborativeTrackChanges',
            'RealTimeCollaborativeRevisionHistory',
            'PresenceList',
            'Comments',
            'TrackChanges',
            'TrackChangesData',
            'RevisionHistory',
            'Pagination',
            'WProofreader',
            // Careful, with the Mathtype plugin CKEditor will not load when loading this sample
            // from a local file system (file://) - load this site via HTTP server if you enable MathType.
            'MathType',
            // The following features are part of the Productivity Pack and require additional license.
            'SlashCommand',
            'Template',
            'DocumentOutline',
            'FormatPainter',
            'TableOfContents'
        ]
    })
    // .then(editor => console.log(Array.from(editor.ui.componentFactory.names())));
}
