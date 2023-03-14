let openForm = (id = 'auth_popup') => document.getElementById(id).style.display = 'flex'
let closeForm = (id = 'auth_popup') => {
    $('#' + id).hide()
}
let closeContainers = () => Array.from(document.getElementsByClassName('auth__container')).forEach(e => e.style.display = 'none')
let openContainer = (id) => $('#' + id).show()
let toForm = (id) => {
    openForm(), closeContainers(), openContainer(id)
}
let loginPopup = () => toForm('login_popup');
let registerPopup = () => toForm('register_popup');
let loginForm = () => toForm('login_form');
let registerForm = () => toForm('register_form');

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


addEventListener('page:before-cache', function () {
    let annotation = document.getElementById('cke_annotation')
    let chapter_content = document.getElementById('cke_chapter_content')
    let editors = [annotation, chapter_content].filter(e => !!e)
    if (editors[0]) {
        editors.forEach(editor => editor.remove())
    }
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
addEventListener('page:before-cache', function () {
    // console.log('page:before-cache')
    $('*[data-header-action]').removeClass('active')
    reInitSelect()

});

addEventListener('page:render', function () {
    // console.log('page:render')
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

