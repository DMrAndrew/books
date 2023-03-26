function initTabs(container) {
  if(!container){
    return;
  }
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
    });
    // check if active
    activeIndex = curLink.classList.contains('active') ? key : activeIndex;

    // hide empty tabs
    for (let tab of tabPanes) {
      if(tab.childElementCount === 0 && curLink.dataset.tab === tab.dataset.tabContent) {
        curLink.style.display = 'none';
        curLink.classList.add('empty');
      }
    }
  }
  // get all links with not empty tabs
  const notEmptyTabLinks = document.querySelectorAll(container + ' > ' + '.ui-tabs-link:not(.empty)');
  // check if find active
  activeIndex = !activeIndex ? 0 : activeIndex;
  if(notEmptyTabLinks.length){
    // click active
    notEmptyTabLinks[activeIndex].click();
  }else{
    //hide all
    document.querySelector(container).closest('.ui-tabs').style.display = 'none';
  }
}