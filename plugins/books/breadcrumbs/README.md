### 1. Информация

При установке плагина в настройках появится пункт Breadcrums, в котором необходимо указать главную страницу, иначе будут подставлены станадртные данные ***(Имя: Главная, ссылка: /)***

Для отображения хлебных крошек на странице по мимо подключения компонента необходимо добавить breadcrums в параметры страницы

```twig
breadcrums = "post"
```

### 2. Регистрация своих генераторов

```php
<?php

use Books\Breadcrumbs\Classes\BreadcrumbsGenerator;
use Books\Breadcrumbs\Classes\BreadcrumbsManager;
use Illuminate\Support\Arr;

/** @var BreadcrumbsManager $manager */
$manager = app(BreadcrumbsManager::class); // получим экземпляр менеджера

/**
 * Home > About
 * url pattern = '/about'
 *
 * Регистрация обычной страницы, так как главная страница всегда имеется вызываем ее с помощью $trail->parent('home');
 */
$manager->register('about', static function (BreadcrumbsGenerator $trail) {
    $trail->parent('home');
    $trail->push('About', url('/about'));
});

/**
 * Home > Blog
 * url pattern = '/blog'
 *
 * Регистрация обычной страницы, так как главная страница всегда имеется вызываем ее с помощью $trail->parent('home');
 */
$manager->register('blog', static function (BreadcrumbsGenerator $trail) {
    $trail->parent('home');
    $trail->push('Blog', url('/blog'));
});

/**
 * Home > Books > [Book]
 * url pattern = '/book-card/:id?'
 *
 * Регистрация динамической страницы в $params получим переменные из паттерна ссылки
 * в данном случае получим title на его основе ищем книгу и добавляем ее в крошки
 */
$manager->register('category', static function (BreadcrumbsGenerator $trail, $params) {
    $trail->parent('blog');

    $book = Book::whereSlug(Arr::get($params, 'title'))->first();

    if ($category) {
        $trail->push($category->title, url('/book-card/', $book->id));
    }
});

/**
 * Home > Blog > [Post]
 * url pattern = '/category/:category/post/:slug?'
 *
 * Регистрация динамической страницы в $params получим переменные из паттерна ссылки
 * в данном случае получим category (его передадим в крошки категории) и slug на его основе ищем пост и добавляем ее в крошки
 */
$manager->register('post', static function (BreadcrumbsGenerator $trail, $params) {
    $trail->parent('home');

    $post = Post::whereSlug(Arr::get($params, 'slug'))->first();

    if ($post) {
        $trail->push($post->title, url('/blog/' . $post->slug));
    }
});
```
