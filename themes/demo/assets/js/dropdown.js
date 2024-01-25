function openDropdown() {
  $('body').on('click', '*', function (e) {
    if ($(e.target).attr('data-dropdown') && $(e.target).attr('data-dropdown') === "button") {
      e.preventDefault();
      e.stopPropagation();
      e.target.classList.toggle('dropdown-menu__button');
    }
  });
}


//dropdowns
window.dropdownsInit = function () {
  const dropdonws = document.querySelectorAll('[data-dropdown]');
  dropdonws.forEach(holder => {
    var links = holder.querySelectorAll('[data-dropdown-link]');
    var close = holder.querySelectorAll('[data-dropdown-close]');
    var dropdowns = holder.querySelectorAll('[data-dropdown-content]');
    var isActive = false;

    function closeAll() {
      holder.classList.remove('active');
      links.forEach(function (link, index) {
        link.classList.remove('active');
      });
      dropdowns.forEach(function (dropdown, index) {
        dropdown.classList.remove('active');
        dropdown.style.display = '';
      });
    }
    if (holder.dataset.tabIsSetted == 'Y' || !links.length || !dropdowns.length) {
      return;
    }
    holder.dataset.tabIsSetted = 'Y';

    holder.addEventListener('click', function (event) {
      event.stopPropagation();
    });
    links.forEach(function (link, index) {
      const curDrop = [...dropdowns].filter(drop => drop.dataset.dropdownContent == link.dataset.dropdownLink)[0];
      link.addEventListener('click', function (event) {
        event.preventDefault();
        holder.classList.toggle('active');
        link.classList.toggle('active');
        if (link.classList.contains('active')) {
          dropdowns.forEach(function (dropdown, index) {
            dropdown.classList.remove('active');
            dropdown.style.display = '';
          });
          curDrop.classList.add('active');
          curDrop.style.display = 'block';
          links.forEach(function (link, index) {
            link.classList.remove('active');
          });
          link.classList.add('active');
        } else {
          dropdowns.forEach(function (dropdown, index) {
            dropdown.classList.remove('active');
            dropdown.style.display = '';
          });
          links.forEach(function (link, index) {
            link.classList.remove('active');
          });
        }
      });
      isActive = link.classList.contains('isActive') ? index : isActive;
    });
    close.forEach(function (closeItem, index) {
      closeItem.addEventListener('click', closeAll);
    });
    if (holder.dataset.bodyClose) {
      document.body.removeEventListener('click', closeAll);
      document.body.addEventListener('click', () => {
        closeAll();
        document.body.removeEventListener('click', closeAll);
      });
    }
    if (isActive !== false) {
      holder.classList.add('active');
      triggerEvent(links[isActive], "click");
    }
  });
}