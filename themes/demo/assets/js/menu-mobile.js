function initFn() {
    // Удалить скрипт (атрибуты). Оживил для презентации заказчику
    const actionItems = document.querySelectorAll('[data-header-action]');

    actionItems.forEach(item => {
        item.addEventListener('click', () => {
            item.classList.toggle('active');
        })
    })

    const overlayMenu = document.querySelector('[data-overlay="menu-mobile"]');
    const buttonMenuMobile = document.querySelectorAll('[data-button-menu="mobile"]');

    for (let i = 0; i < buttonMenuMobile.length; i++) {
        buttonMenuMobile[i].addEventListener('click', () => {
            overlayMenu.classList.toggle('active');

            if (buttonMenuMobile[0].classList.contains('active')) {
                buttonMenuMobile[0].classList.remove('active');
                buttonMenuMobile[1].classList.add('active');
                document.body.classList.add("no-scroll");
                document.querySelector('header.header').classList.add("opened-menu");
            } else {
                buttonMenuMobile[1].classList.remove('active');
                buttonMenuMobile[0].classList.add('active');
                document.body.classList.remove("no-scroll");
                document.querySelector('header.header').classList.remove("opened-menu");
            }
        })
    }

    function initMenuMobile() {
        const menuMobileLink = document.querySelectorAll('[data-menu-link]');
        const menuMobileItem = document.querySelectorAll('[data-menu-item]');


        for (let i = 0; i < menuMobileLink.length; i++) {

            menuMobileLink[i].addEventListener("click", function (e) {
                e.preventDefault();
                let activeTabAttr = menuMobileLink[i].getAttribute("data-menu-link");

                for (let j = 0; j < menuMobileLink.length; j++) {
                    let contentAttr = menuMobileItem[j].getAttribute("data-menu-item");

                    if (activeTabAttr === contentAttr) {
                        menuMobileItem[j].classList.add("active");
                    } else {
                        menuMobileItem[j].classList.remove("active");
                    }
                }

            });
        }
    }

    initMenuMobile();

    const buttonOpenSearchMobile = document.querySelector('[data-button-search="open"]');
    const buttonCloseSearchMobile = document.querySelector('[data-button-search="close"]');
    const buttonClearSearchMobile = document.querySelector('[data-button-input="clear"]');
    const searchItem = document.querySelector('[data-search="item"]');
    const searchInput = document.querySelector('[data-search="input"]');


    if (buttonOpenSearchMobile) {
        buttonOpenSearchMobile.addEventListener('click', () => {
            searchItem.classList.add('active')
        })

        buttonCloseSearchMobile.addEventListener('click', () => {
            searchItem.classList.remove('active')
        })
    }


    function clearInputValue() {

        buttonClearSearchMobile.addEventListener('click', () => {
            searchInput.value = '';
            buttonClearSearchMobile.classList.remove('active');
        });

        searchInput.addEventListener('input', () => {
            if (searchInput.value) {
                buttonClearSearchMobile.classList.add('active');
            } else {
                buttonClearSearchMobile.classList.remove('active');
            }
        })
    }

    clearInputValue();
}
