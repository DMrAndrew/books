<?php

namespace Books\Book\Classes\Converters;

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

    protected string $content;

    protected bool $has_cover = true;

    public function __construct(Book $book)
    {
        parent::__construct($book);
        $this->content = '';
        $this->has_cover = (bool) $book->cover->exists;
    }

    public function generate(): string
    {

        $pattern = collect([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0" xmlns:l="http://www.w3.org/1999/xlink">',
            '%s', //css
            '%s', //description
            '<body><title><p>%s</p></title>%s</body>',
            '%s', //cover
            '</FictionBook>',
        ]);

        $this->content = sprintf($pattern->join(PHP_EOL),
            $this->css(),
            $this->discription(),
            $this->book->title,
            $this->body(),
            $this->has_cover ? $this->create_binary($this->book->cover->getLocalPath()) : ''
        );

        return $this->content;
    }

    public function css(){
        return '';
        return '<stylesheet type="text/css"> %s </stylesheet>';
    }
    public function discription()
    {
        $date = ($this->book->ebook->published_at ?? $this->book->ebook->created_at)->format('Y-m-d');
        $pattern = collect([
            '<description>',
            '<title-info>',
            '%s',
            '<author>%s</author>',
            '<book-title>%s</book-title>',
            '<annotation>%s</annotation>',
            '<keywords>%s</keywords>',
            '<lang>%s</lang>',
            $this->has_cover ? '<coverpage><image l:href="#%s"/></coverpage>' : '',
            sprintf('<date value="%s">%s</date>', $date, $date),
            '</title-info>',
            '<document-info>',
            sprintf('<id>4bef652469d2b65a5dfee7d5bf9a6d75-AAAA-%s</id>', md5($this->book->title)),
            sprintf('<author><nickname>%s</nickname></author>', $this->book->author->profile->username),
            sprintf('<date xml:lang="%s">%s</date>', 'ru', $date),
            sprintf('<version>%s</version>', ''),
            '</document-info>',
            '<publish-info>',
            sprintf(' <book-name>%s</book-name>', $this->book->title),
            sprintf('<publisher>%s</publisher>', ''),
            '</publish-info>',
            '</description>',

        ]);

        return sprintf($pattern->join(PHP_EOL),
            $this->genres($this->book->genres()->pluck('name')->toArray()),
            $this->authors($this->book->profile()->pluck('username')->toArray()),
            $this->book->title,
            $this->section($this->book->annotation),
            $this->book->tags()->pluck('name')->join(', '),
            'ru',
            basename($this->book->cover->path));

    }

    public function section($content, $options = [])
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

        //        $content = $this->enclose_br($content);

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

    public function genres(string|array $genres)
    {
        if (is_string($genres)) {
            return $this->makeGenre($genres);
        }

        return collect($genres)->map(fn ($i) => $this->makeGenre($i))->join('');
    }

    public function makeGenre(string $genre)
    {
        return sprintf('<genre>%s</genre>', $genre);
    }

    public function authors(string|array $authors): string
    {

        if (is_string($authors)) {
            return $this->makeAuthor($authors);
        }

        return collect($authors)->map(fn ($i) => $this->makeAuthor($i))->join('');
    }

    public function makeAuthor(string $string): string
    {
        $array = explode(' ', $string);

        return sprintf('<first-name>%s</first-name>'.'<last-name>%s</last-name>', $array[0] ?? '', $array[1] ?? '');
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

        return '<binary id="'.$filename.'" content-type="image/'.$type.'">'.base64_encode($image).'</binary>'.PHP_EOL;

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

    public function body()
    {

        $chapters = $this->book->ebook->chapters()->get();

        return $chapters->map(function ($chapter) {
            // $content = $this->cleaner->prepare($chapter->content->body, $this->cleaner->strtoarray(collect($this->tags)->join('')));
            $content = $this->replaceEntities($chapter->content->body, collect($this->ENTITIES)->join(''));
            $content = str_replace(PHP_EOL, '', $content);
            $content = $this->section($content);

            $content = preg_replace('/<title>\s*<\/title>/is', '', $content);
            $content = preg_replace('/<section>\s*<\/section>/is', '', $content);
            $content = preg_replace('/<section>\s*<\/section>/is', '', $content);

            return sprintf('<section><title>%s</title>%s</section>', $chapter->title, $content);

        })->join('');

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
