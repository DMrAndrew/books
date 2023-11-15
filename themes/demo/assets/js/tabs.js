function initTabs(container) {
    if (!container) {
        return;
    }
    let hashMatch = false;
    const tabLinks = document.querySelectorAll(container + ' > ' + '.ui-tabs-link');
    const tabPanes = document.querySelectorAll(container + ' > ' + '.ui-tabs-content');
    let activeIndex = null;

    for (let [key, curLink] of tabLinks.entries()) {
        //reset from prev initTabs
        curLink.classList.remove('empty');
        curLink.addEventListener("click", (e) => {
            e.preventDefault();
            let activeTabIndex = curLink.dataset.tab;

            for (let tab of tabPanes) {
                tab.classList.toggle('active', activeTabIndex === tab.dataset.tabContent)
            }
            for (let link of tabLinks) {
                link.classList.toggle('active', activeTabIndex === link.dataset.tab)
            }
            // location.hash = activeTabIndex;
            openDropdown();
        });
        // check if active
        activeIndex = curLink.classList.contains('active') ? key : activeIndex;

        // hide empty tabs
        for (let tab of tabPanes) {
            if (tab.childElementCount === 0 && curLink.dataset.tab === tab.dataset.tabContent) {
                curLink.style.display = 'none';
                curLink.classList.add('empty');
            }
        }

        //hash checker
        // if (location.hash.replace(/[^a-z0-9]/gi, '') == curLink.dataset.tab.replace(/[^a-z0-9]/gi, '')){
        //   hashMatch = key;
        // }
    }
    // get all links with not empty tabs
    const notEmptyTabLinks = document.querySelectorAll(container + ' > ' + '.ui-tabs-link:not(.empty)');
    // check if find active
    activeIndex = hashMatch ? hashMatch : (!activeIndex ? 0 : activeIndex);
    if (notEmptyTabLinks.length) {
        // click active
        notEmptyTabLinks[activeIndex].click();
    } else {
        //hide all
        let el = document.querySelector(container);
        if (el) {
            let closest = el.closest('.ui-tabs')
            if (closest) {
                closest.style.display = 'none';
            }
        }
    }
    // addEventListener("popstate",  ev => {
    //   for (let [key, curLink] of tabLinks.entries()) {
    //     if (location.hash.replace(/[^a-z0-9]/gi, '') == curLink.dataset.tab.replace(/[^a-z0-9]/gi, '')){
    //       curLink.click();
    //     }
    //   }
    // });
}
