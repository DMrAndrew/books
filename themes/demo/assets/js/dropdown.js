function openDropdown() {
  const buttons = document.querySelectorAll('[data-dropdown="button"]');

  buttons.forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();

      let t = e.currentTarget;

      item.classList.toggle('dropdown-menu__button');
    })
  })
}