function openModal(container, buttonOpenModal) {
    const modal = $(container);
    const buttonOpen = $(buttonOpenModal);
    const buttonClose = $(`${container} [data-modal="close"]`);

    console.log('openModal')
    if (!buttonOpen || !buttonOpen || !buttonClose) {
        return
    }
    console.log('openModal bind')
    buttonOpen.click(() => {
        console.log('buttonOpen click')
        modal.addClass(`active`);
        // document.body.style.overflow = 'hidden';
    })

    modal.click(() => {
        console.log('modal click')
        let t = $(this);
        if (t.hasClass(`overlay`)) {
            modal.removeClass(`active`);
            // document.body.style.overflow = 'initial';
        }
    })

    buttonClose.click(() => {
        console.log('buttonClose click')
        modal.removeClass(`active`);
        // document.body.style.overflow = 'initial';
    })
}

