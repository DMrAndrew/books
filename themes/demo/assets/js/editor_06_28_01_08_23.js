function initEditor(inputName, options = []) {

    let {toolbarItems = [],thenFn = (editor) =>{},catchFn = (error) => {} } = options
    CKEDITOR.ClassicEditor.create(document.querySelector(`#${inputName}`), {
        language: 'ru',
        extraPlugins: [SpecialCharactersEmoji],
        toolbar: {
            items: ['undo', 'redo',
                'heading', '|',
                '|', 'bold', 'italic', 'strikethrough', 'underline',
                '|', 'alignment', 'numberedList', 'bulletedList',
                '|', 'link', 'blockQuote', "removeFormat", 'horizontalLine']
                .concat(toolbarItems)
                .concat(['|', "specialCharacters", "findAndReplace", "selectAll",'sourceEditing'])
        },
        // Be careful with the setting below. It instructs CKEditor to accept ALL HTML markup.
        // https://ckeditor.com/docs/ckeditor5/latest/features/general-html-support.html#enabling-all-html-features
        htmlSupport: {
            allow: [
                {
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }
            ],
            disallow: [
                {
                    name: 'script'
                },
                {
                    name: 'iframe'
                },
                {
                    name: 'video'
                },
                {
                    name: 'audio'
                },
            ]
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
            'TableOfContents',
        ]
    })
        .then( editor => thenFn(editor))
        .catch( error => catchFn(error))
    // .then(editor => console.log(Array.from(editor.ui.componentFactory.names())));

}

function SpecialCharactersEmoji(editor) {
    editor.plugins.get('SpecialCharacters').addItems('Emoji', [
        {title: 'smiley face', character: '😊'},
        {title: 'heart', character: '❤️'},
        {title: 'thumbs up', character: '👍'},
        {title: 'clapping hands', character: '👏'},
        {title: 'laughing', character: '😂'},
        {title: 'winking face', character: '😉'},
        {title: 'blushing face', character: '😳'},
        {title: 'grinning face with sweat', character: '😅'},
        {title: 'rolling on the floor laughing', character: '🤣'},
        {title: 'face with tears of joy', character: '😂'},
        {title: 'thinking face', character: '🤔'},
        {title: 'face with monocle', character: '🧐'},
        {title: 'face blowing a kiss', character: '😘'},
        {title: 'exploding head', character: '🤯'},
        {title: 'cowboy hat face', character: '🤠'},
        {title: 'partying face', character: '🥳'},
        {title: 'face with sunglasses', character: '😎'},
        {title: 'hot face', character: '🥵'},
        {title: 'cold face', character: '🥶'},
        {title: 'face screaming in fear', character: '😱'}
    ], {label: 'Emoticons'});
}
