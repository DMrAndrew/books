function initComments() {
    const commentContainer = document.querySelectorAll('.comments-form__container');
    for (let coment of commentContainer) {
        let textarea = coment.querySelector('textarea');
        let button = coment.querySelector('.ui-button');
        textarea.addEventListener('keyup', () => {
            button.classList.toggle('disabled', !textarea.value);
            button.disabled = !textarea.value;
        })
    }

    for (let btn of document.querySelectorAll('[data-collapse-more]')) {
        btn.addEventListener('click', () => {
            $(btn).prev().removeClass('collapsed')
            $(btn).replaceWith('')
        })
    }
}

function sendComment() {

}

function getUnCollapsed() {
    let array = {opened: $(`.comments-comment__sub:not(.collapsed)`).map((l, i) => $(i).data("collapseMoreId")).get()}
    console.log(array)
    return array;
}

