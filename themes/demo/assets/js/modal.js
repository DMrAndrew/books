function openModal(container, buttonOpenModal) {
  const modal = document.querySelector(container);
  const buttonOpen = document.querySelector(buttonOpenModal);
  const buttonClose = document.querySelectorAll(container + ' ' + '[data-modal="close"]');

  if (!buttonOpen || !buttonOpen || !buttonClose) return

  buttonOpen.addEventListener('click', e => {
    e.preventDefault();

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  })

  modal.addEventListener('click', e => {
    let t = e.target;
    if (t.classList.contains('overlay')) {
      modal.classList.remove('active');
      document.body.style.overflow = 'initial';
    }
  })

  for (const btn of buttonClose) {
    btn.addEventListener('click', () => {
      modal.classList.remove('active');
      document.body.style.overflow = 'initial';
    })
  }
}

function setCookie(name, value, expirationInDays) {
  const date = new Date();
  date.setTime(date.getTime() + (expirationInDays * 24 * 60 * 60 * 1000));
  document.cookie = name + '=' + value
    + ';expires=' + date.toUTCString()
    + ';path=/';
}
function cookieExists(name, value) {
  return (document.cookie.split('; ').indexOf(name + '=' + value) !== -1);
}
addEventListener('render', function() {
    $(document).on('click', '.payTypeLabel', function(e) {
        e.preventDefault();
        $('.buy-book-modal__pay').removeClass('active');
        $(this).find('.buy-book-modal__pay').addClass('active');
        $(this).find('input[type="radio"]').prop('checked', true);
    });
});
