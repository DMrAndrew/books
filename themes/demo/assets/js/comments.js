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
}

// document.addEventListener('DOMContentLoaded', () => {
//     initComments()
// });
