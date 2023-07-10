<?php

namespace Books\Book\Classes;

use Books\Book\Models\Content;

class BookUtilities
{

    /**
     * Функция удаляет из HTML все упоминания домена, если он не соответствует заданному.
     * Учитывает http и https.
     */
    public static function removeDomainFromHtml($html, $domain)
    {
        $html = preg_replace('/<a[^>]+href="https?:\/\/(?!' . $domain . ')[^"]+"[^>]*>(.*?)<\/a>/i', '$1', $html);
        $html = preg_replace('/https?:\/\/(?!' . $domain . ')[^"]+/i', '', $html);
        return $html;
    }

    /**
     * Функция заменяет <br> на <p>.
     */
    public static function replaceBrToP($html)
    {
        $html = preg_replace('/<br[^>]*>/i', '</p><p>', $html);
        $html = preg_replace('/<\/p><p>/i', '</p><p>', $html);
        return $html;
    }


    /**
     * Функция преобразует мнемоники в текстовое представление.
     */
    public static function convertMnemonicsToText($mnemonics)
    {
        $mnemonics = str_replace(search: '&#39;', replace: "'", subject: $mnemonics);
        $mnemonics = str_replace('&quot;', '"', $mnemonics);
        $mnemonics = str_replace('&lt;', '<', $mnemonics);
        $mnemonics = str_replace('&gt;', '>', $mnemonics);
        $mnemonics = str_replace('&amp;', '&', $mnemonics);
        $mnemonics = str_replace('&nbsp;', ' ', $mnemonics);
        return $mnemonics;
    }

    public static function convertMnemonicsToTextV1($mnemonics)
    {
        $replace_pairs = array(
            '&#39;' => "'",
            '&quot;' => '"',
            '&lt;' => '<',
            '&gt;' => '>',
            '&amp;' => '&',
            '&nbsp;' => ''
        );

        $mnemonics = strtr($mnemonics, $replace_pairs);
        return $mnemonics;
    }

    /**
     * Удаляет из html все теги и аттрибуты, которые указаны в массивах $exceptTags и $exceptAttributes.
     */
    public function sanitizeHTML(string $html, array $exceptTags = [], array $exceptAttributes = [])
    {

        $exceptTags = array_map('strtolower', $exceptTags);
        $exceptAttributes = array_map('strtolower', $exceptAttributes);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        $xpath = new \DOMXPath($dom);

        // Удаляем теги
        foreach ($exceptTags as $tag) {
            $nodes = $xpath->query("//{$tag}");
            foreach ($nodes as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        // Удаляем аттрибуты
        foreach ($exceptAttributes as $attribute) {
            $nodes = $xpath->query("//*[@{$attribute}]");
            foreach ($nodes as $node) {
                $node->removeAttribute($attribute);
            }
        }

        $html = $dom->saveHTML();
        return $html;
    }

    /**
     * Функция чинит неправильно закрытые теги в html, если закрывающий тэг не совпадает с открывающим
     */
    public static function fixUnclosedTags($html)
    {
        $html = preg_replace('/<([a-z]+)(?: [^>]+)?>((?:(?!<\/\1>).)*)<\/\1>/i', '<$1>$2</$1>', $html);
        return $html;
    }


    public static function test()
    {
    }

    /**
     * Функция подготавливает xml к загрузке в DOMDocument.
     */
    public static function prepareXml($xml)
    {
        $xml = str_replace('&', '&amp;', $xml);
        $xml = str_replace('&amp;amp;', '&amp;', $xml);
        $xml = str_replace('&amp;nbsp;', '&nbsp;', $xml);
        $xml = str_replace('&amp;quot;', '&quot;', $xml);
        $xml = str_replace('&amp;lt;', '&lt;', $xml);
        $xml = str_replace('&amp;gt;', '&gt;', $xml);
        $xml = str_replace('&amp;#39;', '&#39;', $xml);
        $xml = str_replace('&amp;#34;', '&#34;', $xml);
        $xml = str_replace('&amp;#60;', '&#60;', $xml);
        $xml = str_replace('&amp;#62;', '&#62;', $xml);
        $xml = str_replace('&amp;#160;', '&#160;', $xml);

        return $xml;
    }

    /**
     * Функция подготавливает xml к загрузке в DOMDocument.
     */
    public static function prepareXmlv2($xml)
    {
        $xml = str_replace('&', '&amp;', $xml); //символы могут быть заранее экранированы
        $xml = str_replace(array('&amp;amp;', '&amp;quot;', '&amp;lt;', '&amp;gt;',
            '&amp;#39;', '&amp;#34;', '&amp;#60;', '&amp;#62;',
            '&amp;#160;', '&amp;nbsp;', '"', '\'', '<', '>'
        ),
            array('&amp;', '&quot;', '&lt;', '&gt;',
                '&#39;', '&#34;', '&#60;', '&#62;',
                '&#160;', '&nbsp;', '&#34;', '&#39;', '&lt;', '&gt;'
            ), $xml);
        return $xml;
    }

    public static function removeInvalidTags($xml)
    {
        // Заменяем все найденные неправильно закрытые или открытые теги на пустую строку
        $xml = preg_replace('/<[^>]*$/', '', $xml); // Удаляем неправильно открытые теги в конце строки
        $xml = preg_replace('/^[^<]*>/', '', $xml); // Удаляем неправильно закрытые теги в начале строки
        return $xml;
    }

    public static function removeTagsKeepContent($str)
    {
        // Удаляем начальные теги, сохраняем содержимое тегов.
        $str = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/)?>/i", '', $str);

        // Удаляем завершающие теги.
        $str = preg_replace("/<\/([a-z][a-z0-9]*)[^>]*?>/i", '', $str);

        return $str;
    }
}