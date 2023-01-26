function openModal(container, buttonOpenModal) {
    const modal = $(container);
    const buttonOpen = $(buttonOpenModal);
    const buttonClose = $(`${container} [data-modal="close"]`);

    if (!buttonOpen || !buttonOpen || !buttonClose) {
        return
    }
    buttonOpen.click(() => {
        modal.addClass(`active`);
        // document.body.style.overflow = 'hidden';
    })

    modal.click(() => {
        let t = $(this);
        if (t.hasClass(`overlay`)) {
            modal.removeClass(`active`);
            // document.body.style.overflow = 'initial';
        }
    })

    buttonClose.click(() => {
        modal.removeClass(`active`);
        // document.body.style.overflow = 'initial';
    })
}

