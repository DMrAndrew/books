function initTabs(container) {
    const tabLinks = document.querySelectorAll(container + ' > ' + '.ui-tabs-link');
    const tabPanes = document.querySelectorAll(container + ' > ' + '.ui-tabs-content');

    for (let curLink of tabLinks) {
        curLink.addEventListener("click", (e) => {
            e.preventDefault();
            let activeTabIndex = curLink.dataset.tab;

            for (let tab of tabPanes) {
                tab.classList.toggle('active', activeTabIndex === tab.dataset.tabContent)
            }
            for (let link of tabLinks) {
                link.classList.toggle('active', activeTabIndex === link.dataset.tab)
            }
        });
    }
}
