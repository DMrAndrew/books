// Аккордион для Блока "Любимые жанры"
let accordionInit = () => {
    const accrodionHeader = document.querySelector('.favorite-genres__header');
    const MOBILE_POINT = 768;

    if (document.body.offsetWidth < MOBILE_POINT) {
        accrodionHeader.classList.remove('active');
    }

    accrodionHeader.addEventListener('click', function () {
        this.classList.toggle('active');
    })
}

