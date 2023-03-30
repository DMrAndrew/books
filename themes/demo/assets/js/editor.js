function initEditor(inputName, options = []) {

    let {toolbarItems = []} = options
    ClassicEditor.create(document.querySelector(`#${inputName}`), {
        language: 'ru',
        toolbar: {
            items: ['undo', 'redo',
                '|', 'bold', 'italic', 'strikethrough',
                '|', 'numberedList', 'bulletedList',
                '|', 'link', 'blockQuote',].concat(toolbarItems)
        }
    })
    //.then(editor => console.log(Array.from(editor.ui.componentFactory.names())));
}
