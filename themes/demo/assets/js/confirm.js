const ui = {
    confirm: async (message) => createConfirm(message)
}

const createConfirm = (message) => {
    return new Promise((complete, failed)=>{
        $('#confirmMessage').text(message)

        $('#confirmYes').off('click');
        $('#confirmNo').off('click');

        $('#confirmYes').on('click', ()=> { $('#confirm').removeClass('active'); complete(true); });
        $('#confirmNo').on('click', ()=> { $('#confirm').removeClass('active'); complete(false); });

        $('#confirm').addClass('active');
    });
}


const uiconfirm = async (msg = '',yes  = () => {},no = () => {}) => {
    await ui.confirm(msg) ? yes() : no();

}
