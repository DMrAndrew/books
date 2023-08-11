### 1. Информация

При установке плагина в настройках появится пункт Breadcrums, в котором необходимо указать главную страницу, иначе будут подставлены станадртные данные ***(Имя: Главная, ссылка: /)***

Для отображения хлебных крошек на странице по мимо подключения компонента необходимо добавить breadcrums в параметры страницы

```twig
breadcrums = "post"
```

### 2. Регистрация своих генераторов

```php
<?php

use Feedback\Breadcrums\Classes\BreadcrumbsGenerator;
use Feedback\Breadcrums\Classes\BreadcrumbsManager;
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
 * Home > Blog > [Category]
 * url pattern = '/category/:slug?'
 * 
 * Регистрация динамической страницы в $params получим переменные из паттерна ссылки
 * в данном случае получим slug на его основе ищем категорию и добавляем ее в крошки
 */
$manager->register('category', static function (BreadcrumbsGenerator $trail, $params) {
    $trail->parent('blog');
    
    $category = Category::whereSlug(Arr::get($params, 'slug'))->first();

    if ($category) {
        $trail->push($category->title, url('/category/', $category->slug));
    }
});

/**
 * Home > Blog > [Category] > [Post]
 * url pattern = '/category/:category/post/:slug?'
 * 
 * Регистрация динамической страницы в $params получим переменные из паттерна ссылки
 * в данном случае получим category (его передадим в крошки категории) и slug на его основе ищем пост и добавляем ее в крошки
 */
$manager->register('post', static function (BreadcrumbsGenerator $trail, $params) {
    $trail->parent('category', ['slug' => Arr::get($params, 'category')]);

    $post = Post::whereSlug(Arr::get($params, 'slug'))->first();

    if ($post) {
        $trail->push($post->title, url('/category/' . Arr::get($params, 'category') . '/post/' . $post->slug));
    }
});
```

### 3. Еще примеры

```php
<?php

use Feedback\Breadcrums\Classes\BreadcrumbsGenerator;
use Feedback\Breadcrums\Classes\BreadcrumbsManager;
use Illuminate\Support\Arr;

/** @var BreadcrumbsManager $manager */
$manager = app(BreadcrumbsManager::class); // получим экземпляр менеджера

/**
 * Home > Blog > [Category]
 * url pattern = '/category/:slug?'
 * 
 * Добавление в крошки родительсткой категории
 * в данном случае получим slug на его основе ищем категорию и добавляем ее в крошки
 */
$manager->register('category', static function (BreadcrumbsGenerator $trail, $params) {
    $trail->parent('blog');
    
    $category = Category::with('parent')->whereSlug(Arr::get($params, 'slug'))->first();    

    if ($category) {
        // если родительская категория есть, добавим в крошки и ее, но до открытой категории
        if ($category->parent) {
            $trail->push($category->parent->title, url('/category/', $category->parent->slug));
        }    

        $trail->push($category->title, url('/category/', $category->slug));
    }
});
```