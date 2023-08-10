function openModal(container, buttonOpenModal) {
    const modal = document.querySelector(container);
    const buttonOpen = document.querySelector(buttonOpenModal);
    const buttonClose = document.querySelector(container + ' ' + '[data-modal="close"]');
    document.body.style.overflow = 'initial';
    if (!modal  || !buttonClose) return

    buttonOpen?.addEventListener('click', e => {
        openModalFn(e, modal)
    })

    modal.addEventListener('click', e => {
        let t = e.target;
        if (t.classList.contains('overlay')) {
            modal.classList.remove('active');
            document.body.style.overflow = 'initial';
        }
    })

    buttonClose.addEventListener('click', () => {
        closeModalFn(modal)
    })
}

function openModalFn(e, modal) {
    e.preventDefault();

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModalFn(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = 'initial';
}

$(document).on('click', '.payTypeLabel', function(e) {
    e.preventDefault();
    $('.buy-book-modal__pay').removeClass('active');
    $(this).find('.buy-book-modal__pay').addClass('active');
    $(this).find('input[type="radio"]').prop('checked', true);
});
