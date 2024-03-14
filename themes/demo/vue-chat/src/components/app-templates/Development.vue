<template>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <header class="header">
    <div class="ui-container">
      <div class="header__panel">

        <a class="header__logo-wrapper">
          <div class="header__logo"></div>
          <div class="header__logo-text ui-text-body--1 ui-text--bold">Время книг</div>
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
          <div class="header__actions-wrapper">
            <div class="header__actions-item" data-header-action="notifications">

              <div class="ui-label-number _red">4</div>
            </div>

          </div>
          <div class="header__actions-wrapper">
            <div class="header__actions-item">

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
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Фэнтези</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Попаданцы</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Космос</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Проза</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Мистика</a>
                    </li>
                    <li><a href="#"
                           class="menu-book-genres__item ui-text--medium">Бояръ-аниме</a></li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Неформат</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Поэзия</a>
                    </li>
                    <li><a href="#"
                           class="menu-book-genres__item ui-text--medium">Фантастика</a></li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">18+</a></li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Фанфик</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Детское</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Ужасы</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Сказка</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Уся</a></li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Романы</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Детективы</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">РПГ</a></li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Триллер</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Янг-эдалт</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Драма</a>
                    </li>
                    <li><a href="#" class="menu-book-genres__item ui-text--medium">Магический
                      реализм</a></li>
                  </ul>
                  <a href="./all-books.html" class="menu-book-genres__more">

                    <span class="ui-text--medium">Полный список жанров</span>
                  </a>
                </div>
              </div>
            </div>
          </li>
          <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Конкурсы</a></li>
          <li class="nav__list-item"><a href="./library.html" class="ui-text--medium nav__link">Моя
            библиотека</a></li>
          <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Магазин</a></li>
          <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Чат</a></li>
          <li class="nav__list-item"><a href="" class="ui-text--medium nav__link">Реклама</a></li>
        </ul>
      </nav>
    </div>
  </header>
  <slot></slot>
</template>

<script>
import echo from './../../../../assets/js/echo' // inject window.Echo
export default {
  name: "DevelopmentTemplate"
}
</script>
<style scoped>
@import "./../../../../assets/css/main.min.css";
</style>
