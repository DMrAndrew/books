function initEditor(inputName, options = []) {

    let {toolbarItems = []} = options
    ClassicEditor.create(document.querySelector(`#${inputName}`), {
        language: 'ru',
        toolbar: {
            items: ['undo', 'redo',
                '|', 'bold', 'italic',
                '|', 'numberedList', 'bulletedList',
                '|', 'link', 'blockQuote',].concat(toolbarItems)
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
            ]
        },
    })
    //.then(editor => console.log(Array.from(editor.ui.componentFactory.names())));
}
