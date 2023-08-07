let closeForm = (id = 'auth_popup') => {
    $('#' + id).hide()
}

let loginPopup = () => oc.ajax('onGetLoginPopup');
let registerPopup = () => oc.ajax('onGetRegisterPopup');
let loginForm = () => oc.ajax('onGetLoginForm');
let registerForm = () => oc.ajax('onGetRegisterForm');
// let orderFormPopup = () => toForm('order_form_popup');

let openChangerUserNameForm = () => document.getElementById('change_username_form').style.display = 'flex'
let closeChangerUserNameForm = () => document.getElementById('change_username_form').style.display = 'none'
// let closeModal = () => {
//     Array.from(document.getElementsByClassName('ui-modal')).forEach(e => e.style.display = 'none')
// }
let openCreateCycleForm = () => document.getElementById('create_cycle_form').style.display = 'flex'
let closeDropDowns = () => Array.from(document.getElementsByClassName('ui-dropdown')).forEach(e => e.style.display = 'none')

let openTab = function (default_tab) {
    let needle = document.location.hash?.split('#')?.find(e => e.startsWith('tab-'))?.split('-')?.pop() || default_tab;
    let tab = $("div[data-tab-name='" + needle + "']");
    if (tab) {
        tab.click()
    }
}

function initAccordion(container, accordionclickableArea) {
    const nodes = document.querySelectorAll(container);
    const MOBILE_POINT = 768;
    if (document.body.offsetWidth > MOBILE_POINT) return

    nodes[0].classList.add('active');

    Array.from(nodes).forEach(item => {
        item.addEventListener('click', e => {
            let target = e.target.closest(accordionclickableArea);
            if (target) {
                item.classList.toggle('active');
            }
        })
    })
}

addEventListener('page:before-cache', function () {
    $('.ck-editor').remove()
});
let initUserStuff = function () {
    if (Cookies.get('fetch_required')) {
        Cookies.remove('fetch_required')
        oc.ajax('bookAccount::onFetch', {flash: true})
    }
}
let initSortable = (container, handler) => {
    $(function () {
        $(container).sortable({
            scrollSpeed: 10,
            axis: "y",
            dropOnEmpty: false,
            handle: ".handle",
            update: function () {
                let arr = $(container).find('input').map(function () {
                    return $(this).val();
                }).get()
                oc.ajax(handler, {flash: true, data: {sequence: arr, is_owner: $(`input[name=is_owner]`).val()}})
            }
        });
    })
};

let tabElemInit = function () {
    const containerWidth = document.querySelector('.js-container')
    const wrapperWidth = document.querySelector('.js-wrapper')
    const tabElem = document.querySelectorAll('.js-wrapper > .ui-tabs-link')

    tabElem.forEach(() => {
        if (wrapperWidth.clientWidth > containerWidth.clientWidth) {

            const lastTab = wrapperWidth.lastElementChild;
            lastTab.parentNode.removeChild(lastTab)
        }
    })
}
function refreshWidgets(){
    oc.ajax('IndexWidgets::onRefreshWidgets')
}
addEventListener('page:before-cache', function () {
    // console.log('page:before-cache')
    $('*[data-header-action]').removeClass('active')
    reInitSelect()

});

addEventListener('page:render', function () {
    // console.log('page:render')
});


addEventListener('page:load', function () {
     // console.log('page:load')
    if (document.location.pathname === '/'  && document.querySelector('body').dataset.user === "0") {
        refreshWidgets()
    }

});
addEventListener('page:loaded', function () {
     // console.log('page:loaded')
    initUserStuff()
    iniSelect()

});
addEventListener(`DOMContentLoaded`, function () {
    // console.log(`DOMContentLoaded`)
});

addEventListener(`ajax:done`, function () {
});
addEventListener(`page:before-render`, function () {
    // console.log(`page:before-render`)
});

addEventListener(`page:updated`, function () {
    // console.log(`page:updated`)
});


addEventListener('page:unload', function () {
    // console.log('page:unload')

    window.reader && window.reader.clear()
})

