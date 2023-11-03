<?php

namespace Books\Book\Classes\Converters;

use Books\Book\Classes\Enums\ElectronicFormats;
use Books\Book\Models\Book;

/*****************************************************************************************
 *  Класс, преобразует @link \Books\Book\Models\Book в формат FictionBook (fb2)
 ******************************************************************************************/
class FB2 extends BaseConverter
{
    protected array $tags = ['img[src|alt]', 'div[id]', 'blockquote[id]',
        'h1[id]', 'h2[id]', 'h3[id]', 'h4[id]', 'h5[id]', 'h6[id]',
        'hr', 'p[id]', 'br', 'li[id]', 'a[href|name|id]',
        'table[id]', 'tr[align|valign]', 'th[id|colspan|rowspan|align|valign]', 'td[id|colspan|rowspan|align|valign]',
        'b', 'strong', 'i', 'em', 'sub', 'sup', 's', 'strike', 'code'];

    protected array $ENTITIES = ['&amp;', '&lt;', '&gt;', '&apos;', '&quot;', '&nbsp;', '&hellip;', '&ndash;', '&mdash;', '&oacute;'];

    protected array $block_end = ['section', 'title', 'subtitle', 'cite', 'table'];

    protected Cleaner $cleaner;

    protected string $css = '';

    protected string $content;

    protected bool $has_cover = true;

    protected string $cover = '';

    public function __construct(Book $book)
    {
        parent::__construct($book);
        $this->cleaner = new Cleaner();
        $this->content = '';
    }

    public function apply()
    {
        $this->content = $this->cleaner->prepare($this->content, $this->cleaner->strtoarray(collect($this->tags)->join('')));
        $this->content = $this->replaceEntities($this->content, collect($this->ENTITIES)->join(''));
        $this->content = str_replace(PHP_EOL, '', $this->content);

        $pattern = collect([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0" xmlns:l="http://www.w3.org/1999/xlink">',
            '<stylesheet type="text/css"> %s </stylesheet>',
            '%s', //description
            '<body><title<p>%s</p></title>%s</body>',
            '%s', //cover
            '</FictionBook>',
        ]);

        $this->content = sprintf($pattern->join(PHP_EOL),
            $this->css,
            $this->book->annotation,
            $this->book->title,
            $this->content,
            $this->book->ebook->cover->path
        );

        return $this->content;
    }

    public function discription()
    {
        $date = date('Y-m-d');
        $pattern = collect([
            '<description>',
            '<title-info>',
            '<genre>%s</genre>',
            '<author>',
            '<first-name>%s</first-name>',
            '<last-name>%s</last-name>',
            '</author>',
            '<book_title>%s</book_title>',
            '<lang>%s</lang>',
            $this->has_cover ? sprintf('<coverpage><image l:href="#%s"/></coverpage>', $this->cover) : '',
            sprintf('<date value="%s">%s</date>', $date, $date),
            '</title-info>',
            '<document-info>',
            sprintf('<id>4bef652469d2b65a5dfee7d5bf9a6d75-AAAA-%s</id>', md5($this->book->title)),
            sprintf('<author><nickname>%s</nickname></author>', ''),
            sprintf('<date xml:lang="%s">%s</date>', 'ru', $date),
            sprintf('<version>%s</version>', ''),
            '</document-info>',
            '<publish-info>',
            sprintf('<publisher>%s</publisher>', ''),
            '</publish-info>',
            '</description>',

        ]);

        return sprintf($pattern->join(PHP_EOL),
            $this->book->genres()->pluck('name')->join(','),
            $this->book->profile->nickname,
            $this->book->title,
            'ru');

    }

    public function body($content)
    {
        // Заменяем заголовки на жирный текст в ячейках таблиц
        $content = preg_replace_callback('/(<td([^>]*?)>)(.*?)(<\/td>)/is',
            function ($matches) {
                $content = $matches[3];
                $content = preg_replace('/<h([^>]*?)>/is', '<p><strong>', $content);
                $content = preg_replace('/<\/h([^>]*?)>/is', '</strong></p>', $content);

                return $matches[1].$content.$matches[4];
            }, $content);

        // Разбиваем текст на секции
        $template = '/<h([1-3])(.*?)<\/h\1\>/is';
        preg_match_all($template, $content, $matches, PREG_OFFSET_CAPTURE);

        $text = '<section><title></title>';
        $start = 0;
        $prev_level = 1;
        $num_sections = 1;
        $cnt = count($matches[0]);
        for ($i = 0; $i < $cnt; $i++) {    // Разбираем каждый заголовок на патерны
            preg_match($template, $matches[0][$i][0], $mt);

            $level = intval($mt[1]);
            $pos = strpos($mt[2], '>');
            $attr = ($pos > 0) ? substr($mt[2], 0, $pos) : '';
            $txt = substr($mt[2], $pos + 1);
            $section = '</section><section'.$attr.'><title>'.$txt.'</title><section><title></title>';
            if ($prev_level >= $level) {
                $num_ends = $prev_level - $level + 1;
                if ($num_sections < $num_ends) {
                    $num_ends = $num_sections;
                }
                for ($n = 0; $n < $num_ends; $n++) {
                    if (substr($text, -32) == '</title><section><title></title>') {
                        // Удаляем в конце строки <section><title></title>
                        $text = substr_replace($text, '', -24);
                    } else {
                        // Иначе закрываем секцию
                        $section = '</section>'.$section;
                    }
                    $num_sections--;
                }
            }
            $num_sections++;
            $text .= substr($content, $start, $matches[0][$i][1] - $start).$section;
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $prev_level = $level;
        }
        if (substr($text, -32) == '</title><section><title></title>') {
            // Удаляем в конце строки <section><title></title>
            $text = substr_replace($text, '', -24);
            $num_sections--;
        }
        $content = $text.substr($content, $start);
        // Обрамляем тегом <section>
        $content = '<section><title></title>'.$content;
        for ($n = 0; $n < $num_sections + 1; $n++) {
            $content .= '</section>';
        }

        // Обрабатываем заголовки секций
        $content = preg_replace_callback('/(<title>)(.*?)(<\/title>)/is',
            function ($matches) {
                $fb2 = new bgFB2();
                $content = $fb2->section($matches[2], $this->options);

                return $matches[1].$content.$matches[3];
            }, $content);

        // Обрабатываем внутри секций
        $content = preg_replace_callback('/(<\/title>)(.*?)(<\/?section)/is',
            function ($matches) {
                $fb2 = new bgFB2();
                $content = $fb2->section($matches[2], $this->options);

                return $matches[1].$content.$matches[3];
            }, $content);
        // Удаляем лишнее
        $content = preg_replace('/<title>\s*<\/title>/is', '', $content);
        $content = preg_replace('/<section>\s*<\/section>/is', '', $content);
        $content = preg_replace('/<section>\s*<\/section>/is', '', $content);

        return $content;
    }

    public function replaceEntities($content, $str)
    {
        $allow_entities = [];
        // Ключ - HTML-сущность,
        // Значение - её замена на набор символов
        $listattr = explode(',', $str);
        foreach ($listattr as $attr) {
            preg_match('/(&#?[a-z0-9]+;)(\[(.*?)\])?/is', $attr, $mt);
            if (isset($mt[3])) {
                $allow_entities[$mt[1]] = $mt[3];
            } else {
                $allow_entities[$mt[1]] = '';
            }
        }
        // Ищем все вхождения HTML-сущностей
        preg_match_all('/&#?[a-z0-9]+;/is', $content, $matches, PREG_OFFSET_CAPTURE);
        $text = '';
        $start = 0;
        $cnt = count($matches[0]);
        for ($i = 0; $i < $cnt; $i++) {
            // Замена для всех разрешенных HTML-сущностей
            $newmt = $this->checkentity($matches[0][$i][0], $allow_entities);
            $text .= substr($content, $start, $matches[0][$i][1] - $start).$newmt;
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
        }
        $content = $text.substr($content, $start);

        $content = preg_replace_callback('/&([^&]*?[&\s])/is',
            function ($match) {
                if (! preg_match('/&(#?[a-z0-9]+;)/is', $match[0])) {
                    return '&amp;'.$match[1];
                } else {
                    return $match[0];
                }
            }, $content);

        return $content;
    }
}

class bgFB2
{
    public $options = null;

    public function save($file, $content)
    {
        $put = file_put_contents($file, $content);

        return $put;
    }

    public function discription($content, $options)
    {
        $authorName = preg_replace('/\.\,\;\:/is', '', $options['author']);
        $authorName = trim(preg_replace('/\s/is', ' ', $authorName));
        $authorName = explode(' ', $authorName);
        $firstName = (isset($authorName[1])) ? $authorName[1] : '';
        $lastName = (isset($authorName[0])) ? $authorName[0] : '';
        $content = '<description>'.PHP_EOL.
            '<title-info>'.PHP_EOL.
            '<genre>'.$options['genre'].'</genre>'.PHP_EOL.
            '<author>'.PHP_EOL.
            '<first-name>'.$firstName.'</first-name>'.PHP_EOL.
            '<last-name>'.$lastName.'</last-name>'.PHP_EOL.
            '</author>'.
            '<book-title>'.$options['title'].'</book-title>'.PHP_EOL.
            '<lang>'.$options['lang'].'</lang>'.PHP_EOL.
            (($options['cover']) ? ('<coverpage><image l:href="#'.str_replace('gif', 'png', basename($options['cover'])).'"/></coverpage>'.PHP_EOL) : '').
            '<date value="'.date('Y-m-d').'">'.date('d.m.Y').'</date>'.PHP_EOL.
            '</title-info>'.PHP_EOL.
            '<document-info>'.PHP_EOL.
            '<id>4bef652469d2b65a5dfee7d5bf9a6d75-AAAA-'.md5($options['title']).'</id>'.PHP_EOL.
            '<author><nickname>'.$options['author'].'</nickname></author>'.PHP_EOL.
            '<date xml:lang="'.$options['lang'].'">'.date('d.m.Y').'</date>'.PHP_EOL.
            '<version>'.$options['version'].'</version>'.PHP_EOL.
            '</document-info>'.PHP_EOL.
            '<publish-info>'.PHP_EOL.
            '<publisher>'.$options['publisher'].'</publisher>'.PHP_EOL.
            '</publish-info>'.PHP_EOL.
            '</description>';

        return $content;
    }

    public function body($content, $options)
    {
        // Заменяем заголовки на жирный текст в ячейках таблиц
        $content = preg_replace_callback('/(<td([^>]*?)>)(.*?)(<\/td>)/is',
            function ($matches) {
                $content = $matches[3];
                $content = preg_replace('/<h([^>]*?)>/is', '<p><strong>', $content);
                $content = preg_replace('/<\/h([^>]*?)>/is', '</strong></p>', $content);

                return $matches[1].$content.$matches[4];
            }, $content);

        // Разбиваем текст на секции
        $template = '/<h([1-3])(.*?)<\/h\1\>/is';
        preg_match_all($template, $content, $matches, PREG_OFFSET_CAPTURE);

        $text = '<section><title></title>';
        $start = 0;
        $prev_level = 1;
        $num_sections = 1;
        $cnt = count($matches[0]);
        for ($i = 0; $i < $cnt; $i++) {    // Разбираем каждый заголовок на патерны
            preg_match($template, $matches[0][$i][0], $mt);

            $level = intval($mt[1]);
            $pos = strpos($mt[2], '>');
            $attr = ($pos > 0) ? substr($mt[2], 0, $pos) : '';
            $txt = substr($mt[2], $pos + 1);
            $section = '</section><section'.$attr.'><title>'.$txt.'</title><section><title></title>';
            if ($prev_level >= $level) {
                $num_ends = $prev_level - $level + 1;
                if ($num_sections < $num_ends) {
                    $num_ends = $num_sections;
                }
                for ($n = 0; $n < $num_ends; $n++) {
                    if (substr($text, -32) == '</title><section><title></title>') {
                        // Удаляем в конце строки <section><title></title>
                        $text = substr_replace($text, '', -24);
                    } else {
                        // Иначе закрываем секцию
                        $section = '</section>'.$section;
                    }
                    $num_sections--;
                }
            }
            $num_sections++;
            $text .= substr($content, $start, $matches[0][$i][1] - $start).$section;
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $prev_level = $level;
        }
        if (substr($text, -32) == '</title><section><title></title>') {
            // Удаляем в конце строки <section><title></title>
            $text = substr_replace($text, '', -24);
            $num_sections--;
        }
        $content = $text.substr($content, $start);
        // Обрамляем тегом <section>
        $content = '<section><title></title>'.$content;
        for ($n = 0; $n < $num_sections + 1; $n++) {
            $content .= '</section>';
        }

        // Обрабатываем заголовки секций
        $content = preg_replace_callback('/(<title>)(.*?)(<\/title>)/is',
            function ($matches) {
                $fb2 = new bgFB2();
                $content = $fb2->section($matches[2], $this->options);

                return $matches[1].$content.$matches[3];
            }, $content);

        // Обрабатываем внутри секций
        $content = preg_replace_callback('/(<\/title>)(.*?)(<\/?section)/is',
            function ($matches) {
                $fb2 = new bgFB2();
                $content = $fb2->section($matches[2], $this->options);

                return $matches[1].$content.$matches[3];
            }, $content);
        // Удаляем лишнее
        $content = preg_replace('/<title>\s*<\/title>/is', '', $content);
        $content = preg_replace('/<section>\s*<\/section>/is', '', $content);
        $content = preg_replace('/<section>\s*<\/section>/is', '', $content);

        return $content;
    }

    public function  section($content, $options)
    {

        // Преобразуем элементы оформления текста
        $content = str_replace('<b>', '<strong>', $content);
        $content = str_replace('</b>', '</strong>', $content);
        $content = str_replace('<i>', '<emphasis>', $content);
        $content = str_replace('</i>', '</emphasis>', $content);
        $content = str_replace('<em>', '<emphasis>', $content);
        $content = str_replace('</em>', '</emphasis>', $content);
        $content = str_replace('<strike>', '<strikethrough>', $content);
        $content = str_replace('</strike>', '</strikethrough>', $content);
        $content = str_replace('<s>', '<strikethrough>', $content);
        $content = str_replace('</s>', '</strikethrough>', $content);

        // Преобразуем горизонтальную линию в пустую строку
        $content = preg_replace('#<hr([^>]*?)>#is', '<empty-line/>', $content);

        // Преобразуем заголовки h4-h6 в подзаголовки
        $content = preg_replace('/<h[4-6]([^>]*?)>/is', '<subtitle\1>', $content);
        $content = preg_replace('/<\/h[4-6]>/is', '</subtitle>', $content);

        // Цитаты
        $content = preg_replace('/<blockquote([^>]*?)>/is', '<cite\1>', $content);
        $content = str_replace('</blockquote>', '</cite>', $content);

        // Изображения
        $content = preg_replace_callback('/<img\s+([^>]*?)\/>/is',
            function ($match) {
                $attr = preg_replace_callback('/src\s*=\s*([\"\'])([^>]*?)(\1)/is',
                    function ($mt) {
                        $filename = basename($mt[2]);
                        $ext = substr(strrchr($filename, '.'), 1);
                        if ($ext == 'gif') {
                            $filename = str_replace('gif', 'png', $filename);
                            $ext = 'png';
                        }
                        if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png') {
                            return 'l:href="#'.$filename.'"';
                        } else {
                            return '';
                        }
                    }, $match[1]);
                if (preg_match('/l:href/is', $attr)) {
                    return '<image '.$attr.' />';
                } else {
                    return '';
                }
            }, $content);

        // Преобразуем <div> в <p>
        $content = preg_replace('/<div([^>]*?)>/is', '<p\1>', $content);
        $content = str_replace('</div>', '</p>', $content);

        // Преобразуем списки в строки
        $content = preg_replace('/<ol([^>]*?)>/is', '<aid\1>', $content);
        $content = preg_replace('/<ul([^>]*?)>/is', '<aid\1>', $content);
        $content = str_replace('</ol>', '', $content);
        $content = str_replace('</ul>', '', $content);
        $content = preg_replace('/<li([^>]*?)>/is', '<p\1>• ', $content);
        $content = str_replace('</li>', '</p>', $content);

        // Абзацы
        $content = preg_replace('/<p([^>]*?)>/is', '</p><p\1>', $content);
        $content = str_replace('</p>', '</p><p>', $content);

        // Преобразуем <br> в </p><p>

        $content = $this->enclose_br($content);

        $content = preg_replace('#<br([^>]*?)>#is', '</p><p>', $content);

        // Обрабляем содержимое, секции, блоки и абзацы в <p> ... </p>
        $content = str_replace('<title', '</p><title', $content);
        $content = str_replace('</title>', '</title><p>', $content);
        $content = str_replace('<subtitle', '</p><subtitle', $content);
        $content = str_replace('</subtitle>', '</subtitle><p>', $content);
        //		$content = preg_replace('/<image([^>]*?)>/is', '</p><image\1><p>',  $content);	// Убрать комментарии для НЕ inline режима
        $content = str_replace('<empty-line/>', '</p><empty-line/><p>', $content);
        $content = preg_replace('/<cite([^>]*?)>/is', '</p><cite\1><p>', $content);
        $content = str_replace('</cite>', '</p></cite><p>', $content);
        $content = preg_replace('/<table([^>]*?)>/is', '</p><table\1>', $content);
        $content = str_replace('</table>', '</table><p>', $content);
        $content = preg_replace('/<td([^>]*?)>/is', '<td\1><p>', $content);
        $content = str_replace('</td>', '</p></td>', $content);
        $content = '<p>'.$content.'</p>';

        // Убираем лишние <p> и </p>
        $content = preg_replace('/<p>\s*<p([^>]*?)>/is', '<p\1>', $content);
        $content = preg_replace('/<p([^>]*?)>\s*<p([^>]*?)>/is', '<p\1>', $content);
        $content = preg_replace('/<\/p>\s*<\/p>/is', '</p>', $content);
        $content = preg_replace('/<p>\s*<\/p>/is', '', $content);
        $content = preg_replace('/<p>\s*<\/p>/is', '', $content);
        $content = str_replace(PHP_EOL.PHP_EOL, PHP_EOL, $content);

        if (! $options['allow_p']) {
            // В ячейках таблиц абзацы запрещены
            $content = preg_replace_callback('/(<td([^>]*?)>)(.*?)(<\/td>)/is',
                function ($matches) {
                    $content = $matches[3];
                    $content = preg_replace('/<\/p><p([^>]*?)>/is', ' ', $content);
                    $content = preg_replace('/<p([^>]*?)>/is', '', $content);
                    $content = preg_replace('/<\/p>/is', '', $content);
                    $content = preg_replace('/<subtitle([^>]*?)>/is', '', $content);
                    $content = preg_replace('/<\/subtitle>/is', '', $content);
                    $content = preg_replace('/<cite([^>]*?)>/is', '', $content);
                    $content = preg_replace('/<\/cite>/is', '', $content);

                    return $matches[1].$content.$matches[4];
                }, $content);
        }
        // Якори выносим в отдельный тег
        $content = preg_replace_callback('/<a\s+([^>]*?)>/is',
            function ($match) {
                if (preg_match('/(name|id)\s*=\s*([\"\'])([^>]*?)(\2)/is', $match[1], $mt)) {
                    $a = '<aid id="'.$mt[3].'">';
                } else {
                    $a = '';
                }

                if (preg_match('/href\s*=\s*([\"\'])([^>]*?)(\1)/is', $match[1], $mt)) {
                    $a .= '<a l:href="'.$mt[2].'">';
                } else {
                    $a .= '<a>';
                }

                return $a;
            }, $content);

        // Якори (преносим id в элементы <p>, <subtitle>, <v>, <td>)
        $content = preg_replace_callback('/<(p|subtitle|td)(.*?)<aid( id=\"(.*?)\")>/is',
            function ($matches) {
                if (preg_match('/id=\"/is', $matches[2])) {
                    return '<'.$matches[1].$matches[2];
                } else {
                    return '<'.$matches[1].$matches[3].$matches[2];
                }
            }, $content);

        // Удаляем пустые теги и неперенесенные метки
        $content = preg_replace('/<a>(.*?)<\/a>/is', '\1', $content);
        $content = preg_replace('/<aid(.*?)>/is', '', $content);

        return $content;
    }

    public function images($content, $options)
    {
        // Ищем все вхождения изображений
        //$this->images ($content, $options).

        $upload_dir = (object) wp_upload_dir();
        $schema = (@$_SERVER['HTTPS'] == 'on') ? 'https:' : 'http:';
        $domain = '//'.$_SERVER['SERVER_NAME'];
        if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
            $domain .= ':'.$_SERVER['SERVER_PORT'];
        }
        $currentURL = $schema.$domain;
        $rootURI = $_SERVER['DOCUMENT_ROOT'];

        $template = '/<img\s+([^>]*?)src\s*=\s*([\"\'])([^>]*?)(\2)/is';
        preg_match_all($template, $content, $matches, PREG_OFFSET_CAPTURE);

        $text = '';
        $cnt = count($matches[0]);
        for ($i = 0; $i < $cnt; $i++) {
            preg_match($template, $matches[0][$i][0], $mt);
            $path = $mt[3];
            if ($path[0] == '/' && $path[1] != '/') {
                $path = $currentURL.$path;
            } // Задан путь относительно root
            if (! ini_get('allow_url_fopen')) {    // В случае если allow_url_fopen запрещено на сервере
                if (substr($path, 0, 4) == 'http') {
                    $path = str_replace($currentURL, $rootURI, $path);
                } elseif (substr($path, 0, 2) == '//') {
                    $path = str_replace($domain, $rootURI, $path);
                }
            }
            $text .= $this->create_binary($path);
        }

        return $text;
    }

    public function create_binary($path)
    {
        $filename = basename($path);
        $ext = substr(strrchr($filename, '.'), 1);
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $type = 'jpeg';
                break;
            case 'gif':
            case 'png':
                $type = 'png';
                break;
            default:
                return '';
        }
        if ($ext == 'gif') {
            $filename = str_replace('gif', 'png', $filename);
            $image = $this->giftopng($path);
        } else {
            $image = file_get_contents($path);
        }
        if (! $image) {
            return '';
        }
        $text = '<binary id="'.$filename.'" content-type="image/'.$type.'">'.base64_encode($image).'</binary>'.PHP_EOL;

        return $text;
    }

    public function giftopng($path)
    {
        $img = imagecreatefromgif($path);
        $temp_file = tempnam(sys_get_temp_dir(), 'tmp').'.png';

        imagepng($img, $temp_file, 9);
        $image = file_get_contents($temp_file);

        imagedestroy($img);        // Освобождаем память
        unlink($temp_file);    // Удаляем временный файл

        return $image;
    }

    public function replaceEntities($content, $str)
    {
        $allow_entities = [];
        // Ключ - HTML-сущность,
        // Значение - её замена на набор символов
        $listattr = explode(',', $str);
        foreach ($listattr as $attr) {
            preg_match('/(&#?[a-z0-9]+;)(\[(.*?)\])?/is', $attr, $mt);
            if (isset($mt[3])) {
                $allow_entities[$mt[1]] = $mt[3];
            } else {
                $allow_entities[$mt[1]] = '';
            }
        }
        // Ищем все вхождения HTML-сущностей
        preg_match_all('/&#?[a-z0-9]+;/is', $content, $matches, PREG_OFFSET_CAPTURE);
        $text = '';
        $start = 0;
        $cnt = count($matches[0]);
        for ($i = 0; $i < $cnt; $i++) {
            // Замена для всех разрешенных HTML-сущностей
            $newmt = $this->checkentity($matches[0][$i][0], $allow_entities);
            $text .= substr($content, $start, $matches[0][$i][1] - $start).$newmt;
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
        }
        $content = $text.substr($content, $start);

        $content = preg_replace_callback('/&([^&]*?[&\s])/is',
            function ($match) {
                if (! preg_match('/&(#?[a-z0-9]+;)/is', $match[0])) {
                    return '&amp;'.$match[1];
                } else {
                    return $match[0];
                }
            }, $content);

        return $content;
    }

    public function checkentity($mt, $allow_entities)
    {
        foreach ($allow_entities as $entity => $symbols) {
            if ($entity == $mt) {
                if ($symbols == '') {
                    return $entity;
                }        // Если не задана замена, оставляем HTML-сущность
                else {
                    return $symbols;
                }                    // иначе, замещаем на символы
            }
        }

        return '';                                        // Остальные HTML-сущности удаляем
    }

    public function enclose_br($text)
    {
        $tagstack = [];
        $newtext = '';

        // Теги форматирования текста
        $text_formatting_tags = ['strong', 'emphasis', 'u', 'strikethrough', 'sup', 'sub', 'code', 'a'];

        $text = preg_replace('#<br([^>]*?)>(\r?\n?)+#is', '<br>', $text);
        // Просмотрим все теги
        while (preg_match("/<(\/?[\w:]*)\s*([^>]*)>/", $text, $regex)) {
            $i = strpos($text, $regex[0]);    // Позиция найденного тега
            $l = strlen($regex[0]);            // Кол-во символов в теге

            // Завершающий тег
            if (isset($regex[1][0]) && '/' == $regex[1][0]) {
                $tagname = strtolower(substr($regex[1], 1));
                $tag = '</'.$tagname.'>';

                // Если это тег форматирования текста
                if (in_array($tagname, $text_formatting_tags)) {
                    // Проверим, нет ли открывающего тега в стеке
                    $stacksize = count($tagstack);
                    for ($k = $stacksize - 1; $k >= 0; $k--) {
                        // Если есть такой же, то сотрем его
                        if ($tagstack[$k] == $tagname) {
                            $tagstack[$k] = '';
                        }
                    }
                }
            } else { // Открывающий тег
                $tagname = strtolower($regex[1]);
                $tag = '<'.$tagname.(($regex[2]) ? (' '.$regex[2]) : '').'>';

                // Если это тег форматирования текста
                if (in_array($tagname, $text_formatting_tags)) {
                    $stacksize = array_push($tagstack, $tagname);    // Поместить тег в стек
                    // Если это тег переноса строки
                } elseif ($tagname == 'br') {
                    // Просмотрим весь стек
                    $stacksize = count($tagstack);
                    for ($k = 0; $k < $stacksize; $k++) {
                        // Если в стеке есть незакрытые теги форматирования текста,
                        if ($tagstack[$k]) {
                            // то обрамим тег <br> этими тегами: спереди завершающие, сзади - открывающие
                            $tag = '</'.$tagstack[$k].'>'.$tag.'<'.$tagstack[$k].'>';
                        }
                    }
                }
            }
            $newtext .= substr($text, 0, $i).$tag;
            $text = substr($text, $i + $l);
        }
        // Добавить оставшийся текст
        $newtext .= $text;

        return $newtext;
    }
}

class Cleaner
{
    // Оставляет в тексте только разрешенные теги и атрибуты
    public function prepare($content, $allow_attributes)
    {
        // Удаляем JS-скрипты
        $content = preg_replace('/<script.*?script>/s', '', $content);
        // Заменяем <br/> и <hr/> на <br /> и <hr />
        $content = str_replace('<br/>', '<br />', $content);
        $content = str_replace('<hr/>', '<hr />', $content);

        // Списки
        if (! array_key_exists('ol', $allow_attributes)) {
            // Заменяем нумерованный список на ненумерованный
            $content = str_replace('<оl', '<ul', $content);
            $content = str_replace('</оl>', '</ul>', $content);
        } else {
            if (! array_key_exists('ul', $allow_attributes)) {
                $allow_attributes['ul'] = '';
            }
            if (! array_key_exists('li', $allow_attributes)) {
                $allow_attributes['li'] = '';
            }
        }
        if (! array_key_exists('ul', $allow_attributes)) {
            // Заменяем списки на абзацы
            $content = preg_replace('/<li(.*?)>/is', '<p\1>• ', $content);
            $content = str_replace('</li>', '</p>', $content);
        } else {
            if (! array_key_exists('li', $allow_attributes)) {
                $allow_attributes['li'] = '';
            }
        }

        // Блоки и заголовки
        $headers = ['div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre'];
        foreach ($headers as $tag) {
            if (! array_key_exists($tag, $allow_attributes)) {
                // Заменяем блоки на абзацы
                $content = preg_replace('/<'.$tag.'(.*?)>/is', '<p\1>', $content);
                $content = str_replace('</'.$tag.'>', '</p>', $content);
            }
        }

        // Таблицы
        if (! array_key_exists('table', $allow_attributes)) {
            unset($allow_attributes['thead'], $allow_attributes['tbody'], $allow_attributes['tfoot'], $allow_attributes['th'], $allow_attributes['td']);
            $content = preg_replace('/<tr(.*?)>/is', '<p\1>• ', $content);
            $content = str_replace('</tr>', '</p>', $content);
        } else {
            if (! array_key_exists('tr', $allow_attributes)) {
                $allow_attributes['tr'] = '';
            }
            if (! array_key_exists('th', $allow_attributes)) {
                $allow_attributes['th'] = '';
            }
            if (! array_key_exists('td', $allow_attributes)) {
                $allow_attributes['td'] = '';
            }
        }

        // Удаляем теги внутри всех заголовков, кроме ссылок
        $content = preg_replace_callback('/<(h[1-6])(.*?)>(.*?)<\/\1>/is',
            function ($match) {
                return '<'.$match[1].$match[2].'>'.strip_tags($match[3], '<a>').'</'.$match[1].'>';
            }, $content);

        // Удаляем все теги кроме разрешенных
        $allow_tags = '';
        foreach ($allow_attributes as $tag => $attr) {
            $allow_tags .= '<'.$tag.'>';
        }
        $content = strip_tags($content, $allow_tags);

        // Проверяем все оставшиеся открывающие теги и их атрибуты
        $template = '/<([a-z][a-z0-9]*\b)([^>]*?)(\/?\>)/is';
        preg_match_all($template, $content, $matches, PREG_OFFSET_CAPTURE);

        $text = '';
        $start = 0;
        $cnt = count($matches[0]);
        for ($i = 0; $i < $cnt; $i++) {    // Разбираем каждый тэг на патерны
            preg_match($template, $matches[0][$i][0], $mt);
            $tag = $mt[1];                            // Имя тега
            $newattr = '<'.$tag;
            if ($allow_attributes[$tag]) {
                if ($allow_attributes[$tag] == '*') {
                    $newattr .= $mt[2];                // Все атрибуты
                } else {
                    $attrs = explode('|', $allow_attributes[$tag]);
                    foreach ($attrs as $attr) {        // Разрешенные атрибуты
                        if (preg_match('/'.$attr.'\s*=\s*([\"\'])(.*?)(\1)/is', $mt[2], $value)) {
                            $newattr .= ' '.$attr.'="'.str_replace('"', "'", $value[2]).'"';
                        }
                    }
                }
            }
            $newattr .= $mt[3];                        // Закрывающие символы: /> или >

            $text .= substr($content, $start, $matches[0][$i][1] - $start).str_ireplace($mt[0], $newattr, $matches[0][$i][0]);
            $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
        }
        $content = $text.substr($content, $start);

        return $content;
    }

    // Формирует массив разрешенных тегов и атрибутов из строки
    public function strtoarray($str)
    {
        $allow_attributes = [];
        // Ключ - тег,
        // Значение - перечень разрешенных атрибутов, разделенных вертикальной чертой,
        // если Значение "*" - разрешены все атрибуты
        $str = preg_replace('/\s+/is', '', $str);
        $listattr = explode(',', $str);
        foreach ($listattr as $attr) {
            preg_match('/([a-z0-9]+)(\[([\|a-z0-9]+)\])?/is', $attr, $mt);
            if (isset($mt[3])) {
                $allow_attributes[$mt[1]] = $mt[3];
            } else {
                $allow_attributes[$mt[1]] = '';
            }
        }

        return $allow_attributes;
    }

    // Добавляет символы конца строки к закрывающим тегам блоков и строк, в также к тегу br
    public function addEOL($content)
    {
        // Делаем текст кода читабельным
        $lines = ['html', 'head', 'body', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'p', 'ol', 'ul'];
        foreach ($lines as $tag) {
            $content = preg_replace('#</'.$tag.'>\s*#is', '</'.$tag.'>'.PHP_EOL, $content);
        }
        $content = preg_replace('#</li>\s*#is', '</li>'.PHP_EOL, $content);
        $content = preg_replace('#<br>\s*#is', '<br>'.PHP_EOL, $content);
        $content = preg_replace('#<br />\s*#is', '<br />'.PHP_EOL, $content);

        return $content;
    }

    public function replaceSpaces($content)
    {

        // Заменяем &nbsp; на пробел
        $content = str_replace('&nbsp;', ' ', $content);
        // Удаляем двойные пробелы
        $content = preg_replace('/\s+/s', ' ', $content);
        $content = str_replace('  ', ' ', $content);
        // Удаляем пробелы из начала и конца строки
        $content = trim($content);

        return $content;
    }
}
