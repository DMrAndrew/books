function openModal(container, buttonOpenModal) {
    const modal = document.querySelector(container);
    const buttonOpen = document.querySelector(buttonOpenModal);
    const buttonClose = document.querySelector(container + ' ' + '[data-modal="close"]');

    if (!buttonOpen || !buttonOpen || !buttonClose) return

    buttonOpen.addEventListener('click', e => {
        e.preventDefault();

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    })

    modal.addEventListener('click', e => {
        let t = e.target;
        if (t.classList.contains('overlay')) {
            modal.classList.remove('active');
            document.body.style.overflow = 'initial';
        }
    })

    buttonClose.addEventListener('click', () => {
        modal.classList.remove('active');
        document.body.style.overflow = 'initial';
    })
}

