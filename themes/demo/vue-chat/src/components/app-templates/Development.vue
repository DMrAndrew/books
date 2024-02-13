<template>
  <div>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <header class="header">
      <div class="ui-container">
        <div class="header__panel">

          <a  class="header__logo-wrapper">
            <div class="header__logo"></div>
            <div  class="header__logo-text ui-text-body--1 ui-text--bold">Время книг</div>
          </a>
          <div class="header__search-wrapper"></div>
          <!-- <div class="header__actions-auth">
            <button class="header__actions-auth-button ui-button ui-button-view--3 ui-button-size--32">Войти</button>
            <button class="header__actions-auth-button ui-button ui-button-view--2 ui-button-size--32">Зарегистрироваться</button>
          </div> -->
          <div class="header__actions">
            <div class="header__actions-wrapper">
              <div class="header__actions-item" data-header-action="message">

                <div class="ui-label-number _red">412</div>
              </div>

            </div>
            <div class="header__actions-wrapper" >
              <div class="header__actions-item" data-header-action="notifications">

                <div class="ui-label-number _red">4</div>
              </div>

            </div>
            <div class="header__actions-wrapper">
              <div class="header__actions-item" >

              </div>
            </div>
            <div class="header__actions-wrapper">
              <div class="header__actions-item" data-header-action="wallet">

                <div class="ui-text--medium">16 500 ₽</div>
              </div>

            </div>
            <div class="header__actions-wrapper">
              <div class="header__actions-item-logo" data-header-action="profile">

              </div>


            </div>
          </div>
          <svg class="header__menu-search square-24">

          </svg>
        </div>
        <nav class="header__nav nav">
          <ul class="nav__list">
            <li class="nav__list-item _dropdown">
              <a class="ui-text--medium nav__link _dropdown" href="#">
                <span>Книги</span>

              </a>

              <!-- dropdown -->
              <div class="dropdown-books">
                <div class="dropdown-books__content">
                  <div class="book-type _separate">
                    <a href="./listing.html" class="book-type__item">
                      <div class="book-type__icon-wrapper">

                      </div>
                      <div class="book-type__text ui-text--medium">Все книги</div>
                    </a>
                    <a href="#" class="book-type__item">
                      <div class="book-type__icon-wrapper">

                      </div>
                      <div class="book-type__text ui-text--medium">Электронные</div>
                    </a>
                    <a href="#" class="book-type__item">
                      <div class="book-type__icon-wrapper">

                      </div>
                      <div class="book-type__text ui-text--medium">Аудио</div>
                    </a>
                    <a href="#" class="book-type__item">
                      <div class="book-type__icon-wrapper">

                      </div>
                      <div class="book-type__text ui-text--medium">Бумажные</div>
                    </a>
                    <a href="#" class="book-type__item">
                      <div class="book-type__icon-wrapper">

                      </div>
                      <div class="book-type__text ui-text--medium">Комиксы</div>
                    </a>
                  </div>
                  <div class="menu-book-genres">
                    <div class="menu-book-genres__title ui-text-head--3 ui-text--bold">Жанры</div>
                    <ul class="menu-book-genres__list">
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Фэнтези</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Попаданцы</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Космос</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Проза</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Мистика</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Бояръ-аниме</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Неформат</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Поэзия</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Фантастика</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">18+</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Фанфик</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Детское</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Ужасы</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Сказка</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Уся</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Романы</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Детективы</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">РПГ</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Триллер</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Янг-эдалт</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Драма</a></li>
                      <li><a href="#" class="menu-book-genres__item ui-text--medium">Магический реализм</a></li>
                    </ul>
                    <a href="./all-books.html" class="menu-book-genres__more">

                      <span class="ui-text--medium">Полный список жанров</span>
                    </a>
                  </div>
                </div>
              </div>
            </li>
            <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Конкурсы</a></li>
            <li class="nav__list-item"><a href="./library.html" class="ui-text--medium nav__link">Моя библиотека</a></li>
            <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Магазин</a></li>
            <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Чат</a></li>
            <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Реклама</a></li>
          </ul>
        </nav>
      </div>
    </header>
      <div class="ui-container">
          <div class="ui-text-head--2 ui-text--bold page-title">Чат</div>
      </div>

    <slot></slot>

      <footer class="footer _color" style="margin-top: 100px">
          <div class="ui-container">
              <div class="footer__warning">
                  <div class="footer__warning-label">18+</div>
                  <span>Внимание! Сайт может содержать материалы, не предназначенные для просмотра лицами, не достигшими 18 лет</span>
              </div>
              <nav class="footer__nav ui-grid-container ui-grid-gap">
                  <div class=" ui-col-md-4 ui-col-sm-6">
                      <a href="#" target="_blank" class="footer__link">Новости</a>
                      <a href="/copyright-holders" target="_blank" class="footer__link">Правообладателям</a>
                      <a href="/faq" target="_blank" class="footer__link">Справка авторам и читателям</a>
                      <!--                <a href="#" target="_blank" class="footer__link">Оплата</a>-->
                      <a href="/about" target="_blank" class="footer__link">О сайте</a>
                      <a href="/contacts" target="_blank" class="footer__link">Контакты</a>
                  </div>
                  <div class="ui-col-md-4 ui-col-sm-6">
                      <a href="/terms-of-use" target="_blank" class="footer__link">Пользовательское соглашение</a>
                      <a href="/privacy-agreement" target="_blank" class="footer__link">Соглашение о конфиденциальности</a>
                      <a href="/edoc" target="_blank" class="footer__link">Электронный документооборот</a>
                  </div>
                  <div class="footer-social ui-col-md-4 ui-col-sm-6">
                      <span>Мы в социальных сетях</span>
                      <a href="https://vk.com/bookstimeru" target="_blank">
                          <svg>
                              <use xlink:href="{{'assets/icon-sprite/svg-sprite.svg#logo-vk-28' | theme}}"></use>
                          </svg>
                      </a>
                      <a href="https://t.me/+bnniJiUjLmZmNGI6" target="_blank">
                          <svg>
                              <use xlink:href="{{'assets/icon-sprite/svg-sprite.svg#logo-telegram-28' | theme}}"></use>
                          </svg>
                      </a>
                      <a href="https://ok.ru/group/70000002517701" target="_blank">
                          <svg>
                              <use xlink:href="{{'assets/icon-sprite/svg-sprite.svg#logo-odnaklasniki-28' | theme}}"></use>
                          </svg>
                      </a>
                      <a href="https://dzen.ru/user/6udklzj9l8okbmo81gi8kcojey0" target="_blank">
                          <svg>
                              <use xlink:href="{{'assets/icon-sprite/svg-sprite.svg#logo-zen-28' | theme}}"></use>
                          </svg>
                      </a>

                  </div>
              </nav>
              <div class="footer__footer">©  Время книг, Разработка сайта - <a
                  href="https://mobecan.com/" rel="nofollow">Mobecan</a></div>
          </div>
      </footer>
  </div>
</template>

<script>
export default {
  name: "DevelopmentTemplate"
}
</script>
<style scoped>
@import "http://127.0.0.1:8000/themes/demo/assets/css/main.min.css?v=19012024";
</style>
