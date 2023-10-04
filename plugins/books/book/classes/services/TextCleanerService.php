<?php declare(strict_types=1);

namespace Books\Book\Classes\Services;

use App;
use Books\Book\Classes\Exceptions\TextContentLinkDomainException;
use Books\Book\Classes\Exceptions\TextContentWrongLinkException;
use Config;
use DOMDocument;
use DOMNode;
use DOMText;
use Exception;
use Str;

class TextCleanerService
{
    const PROCESS_LINK_MODE_RETURN_ERROR = 1; // возвращать ошибку, останавливать
    const PROCESS_LINK_MODE_EXTRACT_ANCHOR = 2; // удалять ссылку, оставлять её текст в контенте
    const PROCESS_LINK_MODE_IGNORE = 3; // игнорировать

    const DEFAULT_ALLOW_TAGS = [
        // js editor tags
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'p', 'span', 'strong', 'b',  'i', 's', 'u', 'ol', 'ul', 'li', 'a', 'img', 'blockquote'
    ];

    const DEFAULT_ALLOW_ATTRIBUTES = [
        'src', // images <img src=..>
        'href', // links <a href=..>
        'class', // for js editor image resize
        'style',
        'width',
    ];

    const DEFAULT_ALLOW_CLASSES = [
        'image_resized', // js editor image resize
    ];

    const DEFAULT_ALLOW_INLINE_STYLES = [
        'text-align', // js editor left|center|right align
        'width', // js editor image resize
    ];

    /**
     * @throws Exception
     */
    public static function cleanContent(
        ?string $inputContent,
        array $allowTags = self::DEFAULT_ALLOW_TAGS,
        array $allowAttributes = self::DEFAULT_ALLOW_ATTRIBUTES,
        array $allowClasses = self::DEFAULT_ALLOW_CLASSES,
        array $allowInlineStyles = self::DEFAULT_ALLOW_INLINE_STYLES,
        int $processLinksMode = self::PROCESS_LINK_MODE_RETURN_ERROR,

    ): ?string
    {
        if ($inputContent === null) {
            return null;
        }

        /**
         * Add safe container for paginated content
         */
        $inputContent = "<p>{$inputContent}</p>";

        $inputContent = Str::squish(trim($inputContent));
        if (mb_strlen($inputContent) == 0) {
            return $inputContent;
        }

        /**
         * Clean Tags
         */
        $allowable_tags = self::prepareTags($allowTags);
        $cleanedContent = strip_tags($inputContent, $allowable_tags);

        /**
         * Parse content html
         */
        try{
            $doc = new DOMDocument('1.0', 'utf-8');
            $encodedContent = mb_convert_encoding($cleanedContent, 'HTML-ENTITIES', 'UTF-8');
            //$doc->loadHTML($encodedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            @$doc->loadHTML($encodedContent, LIBXML_HTML_NOIMPLIED | LIBXML_BIGLINES | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE);

            /**
             * Clean attributes
             */
            self::cleanAttributes($doc, array_merge($allowAttributes, ['#text']));

            /**
             * Clean classes
             */
            self::cleanClases($doc, array_merge($allowClasses, ['#text']));

            /**
             * Clean styles
             */
            self::cleanStyles($doc, array_merge($allowInlineStyles, ['#text']));

            /**
             * Remove empty tags
             */
            // todo

            /**
             * Remove empty attributes
             */
            // todo

            /**
             * Check links domains
             */
            self::validateLinkProcessMode($processLinksMode);
            if ( $processLinksMode != self::PROCESS_LINK_MODE_IGNORE) {
                $allowDomains = self::getAppDomainWhitelist();
                self::validateLinkHrefDomains($doc, $allowDomains, $processLinksMode);
            }

        } catch (TextContentWrongLinkException|TextContentLinkDomainException $e) {
            throw new Exception($e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Похоже текст, который вы пытаетесь сохранить имеет невалидное форматирование.');
        }

        /**
         * saveHTML() can add extra empty tags (<p>)
         */
        $outputHtml = html_entity_decode($doc->saveHTML());

        /**
         * Remove double spaces, `&nbsp`, etc
         */
        $outputHtmlWithCleanedSpaces = self::cleanSpaces($outputHtml);

        /**
         * Remove empty paragraphs
         */
        $output = self::cleanEmptyParagraphs($outputHtmlWithCleanedSpaces);

        /**
         * Remove temporary save container
         */
        $r = preg_replace('/^<p>/', '', $output, 1);
        return preg_replace('/<\/p>$/', '', $r, 1);
    }

    /**
     * @param DOMNode $domNode
     * @param array $allowAttributes
     *
     * @return void
     */
    public static function cleanAttributes(DOMNode &$domNode, array $allowAttributes): void
    {
        /** @var DOMNode $node */
        foreach ($domNode->childNodes as $nodeKey => $node)
        {
            $attributes = $node->attributes;
            if ($attributes) {
                foreach ($attributes as $attr) {

                    $attributeName = $attr->nodeName;
                    $attributeValue = $attr->nodeValue;

                    if (!in_array($attributeName, $allowAttributes)) {
                        $domNode->childNodes[$nodeKey]->removeAttributeNode($attr);

                        /**
                         * Вызываем еще раз, так как удаление аттрибута останавливает обход
                         */
                        self::cleanAttributes($domNode, $allowAttributes);
                    }
                }
            }

            if($node->hasChildNodes()) {
                self::cleanAttributes($node, $allowAttributes);
            }
        }
    }

    /**
     * @param DOMNode $domNode
     * @param array $allowClasses
     *
     * @return void
     */
    public static function cleanClases(DOMNode &$domNode, array $allowClasses): void
    {
        /** @var DOMNode $node */
        foreach ($domNode->childNodes as $nodeKey => $node)
        {
            $attributes = $node->attributes;
            if ($attributes) {
                foreach ($attributes as $attr) {

                    $attributeName = $attr->nodeName;
                    $attributeValue = $attr->nodeValue;

                    if ($attributeName === "class") {
                        if (!in_array($attributeValue, $allowClasses)) {
                            $domNode->childNodes[$nodeKey]->removeAttributeNode($attr);

                            /**
                             * Вызываем еще раз, так как удаление аттрибута останавливает обход
                             */
                            self::cleanClases($domNode, $allowClasses);
                        }
                    }
                }
            }

            if($node->hasChildNodes()) {
                self::cleanClases($node, $allowClasses);
            }
        }
    }

    /**
     * @param DOMNode $domNode
     * @param array $allowInlineStyles
     *
     * @return void
     */
    public static function cleanStyles(DOMNode &$domNode, array $allowInlineStyles): void
    {
        /** @var DOMNode $node */
        foreach ($domNode->childNodes as $nodeKey => $node)
        {
            $attributes = $node->attributes;
            if ($attributes) {
                foreach ($attributes as $attr) {

                    $attributeName = $attr->nodeName;
                    $attributeValue = $attr->nodeValue;

                    if ($attributeName === "style") {

                        $filteredStyles = [];

                        $usedStyles = explode(';', $attributeValue);
                        foreach ($usedStyles as $usedStyle) {
                            if (mb_strlen($usedStyle) > 0 && str_contains($usedStyle, ':')) {

                                /**
                                 * Whitelist styles
                                 */
                                @[$styleName, $styleValue] = explode(':', $usedStyle);
                                if (in_array($styleName, $allowInlineStyles)) {
                                    $filteredStyles[$styleName] = $styleValue;
                                }
                            }
                        }

                        $combineStyles = [];
                        foreach ($filteredStyles as $filteredStyle => $filteredValue) {
                            $combineStyles[] = $filteredStyle . ':' . $filteredValue;
                        }

                        $filteredInlineStyles = implode(';', $combineStyles);

                        if ($filteredInlineStyles !== $attributeValue) {
                            $domNode->childNodes[$nodeKey]->setAttribute('style', $filteredInlineStyles);

                             /**
                             * Вызываем еще раз, так как изменение аттрибута останавливает обход
                             */
                            self::cleanStyles($domNode, $allowInlineStyles);
                        }
                    }
                }
            }

            if($node->hasChildNodes()) {
                self::cleanStyles($node, $allowInlineStyles);
            }
        }
    }

    /**
     * @param DOMNode $domNode
     * @param array $allowDomains
     * @param int $processLinksMode
     *
     * @return void
     * @throws TextContentLinkDomainException
     * @throws TextContentWrongLinkException
     */
    public static function validateLinkHrefDomains(DOMNode &$domNode, array $allowDomains, int $processLinksMode): void
    {
        /** @var DOMNode $node */
        foreach ($domNode->childNodes as $nodeKey => $node)
        {

            $attributes = $node->attributes;
            if ($attributes) {

                foreach ($attributes as $attr) {

                    $attributeName = $attr->nodeName;
                    $attributeValue = $attr->nodeValue;

                    if ($attributeName === "href") {
                        /**
                         * Mode: возвращать ошибки
                         */
                        if ($processLinksMode === self::PROCESS_LINK_MODE_RETURN_ERROR) {
                            if (mb_strlen($node->textContent) === 0) {
                                throw new TextContentWrongLinkException("У ссылки `{$attributeValue}` - отсутствует якорь.");
                            }

                            $urlHost = parse_url(trim($attributeValue), PHP_URL_HOST);
                            if ($urlHost === null) {
                                throw new TextContentWrongLinkException("`{$node->textContent}` - Пустая ссылка или ссылка с некорректным адресом.");
                            }

                            if (!in_array($urlHost, $allowDomains)) {
                                throw new TextContentLinkDomainException("Ссылка `{$node->textContent}` содержит недопустимый адрес. Разрешаются ссылки только на внутренние страницы сервиса.");
                            }
                        }

                        /**
                         * Mode: заменять текст
                         */
                        else if ($processLinksMode === self::PROCESS_LINK_MODE_EXTRACT_ANCHOR) {

                            $urlHost = parse_url(trim($attributeValue), PHP_URL_HOST);

                            /**
                             * Отсутствует якорь - удалить (заменить пустой строкой)
                             */
                            if (mb_strlen($node->textContent) === 0) {
                                $domNode->childNodes[$nodeKey]->parentNode->replaceChild(new DOMText(''), $node);
                                self::validateLinkHrefDomains($domNode, $allowDomains, $processLinksMode);

                                return;
                            }

                            /**
                             * Пустая ссылка - удалить (заменить пустой строкой)
                             */
                            else if ($urlHost === null) {
                                $domNode->childNodes[$nodeKey]->parentNode->replaceChild(new DOMText(''), $node);
                                self::validateLinkHrefDomains($domNode, $allowDomains, $processLinksMode);

                                return;
                            }

                            /**
                             * Запрещенный домен - заменить ссылку на анкор
                             */
                            else if (!in_array($urlHost, $allowDomains)) {
                                $domNode->childNodes[$nodeKey]->parentNode->replaceChild(new DOMText($node->textContent), $node);
                                self::validateLinkHrefDomains($domNode, $allowDomains, $processLinksMode);

                                return;
                            }
                        }
                    }
                }
            }

            if($node->hasChildNodes()) {
                self::validateLinkHrefDomains($node, $allowDomains, $processLinksMode);
            }
        }
    }

    /**
     * input: array of tags
     * output: string of tags (example: `<p><a><img><ul><ol><li><table><thead><tbody><tr><th><td>`)
     *
     * @param array $allowTags
     *
     * @return string
     */
    private static function prepareTags(array $allowTags): string
    {
        $tagsWithBraces = array_map(fn($allowTag) => "<$allowTag>", $allowTags);

        return implode('', $tagsWithBraces);
    }

    /**
     * @return string[]
     */
    private static function getAppDomainWhitelist(): array
    {
        $domains = Config::get('books.book.allowed_reader_domains');
        $domains[] = parse_url(config('app.url'), PHP_URL_HOST);

        if (App::environment() === 'local') {
            $domains[] = 'localhost';
            $domains[] = '127.0.0.1';
        }

        return array_unique($domains);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    private static function cleanSpaces(string $html): string
    {
        /**
         * Convert spaces of all sizes to a standard space
         */
        $htmlWithRegularSpaces = preg_replace('~\s+~u', ' ', $html);

        /**
         * Remove double spaces
         */
        $htmlWithoutDoubleSpaces = preg_replace('/\s+/', ' ', $htmlWithRegularSpaces);

        return Str::squish($htmlWithoutDoubleSpaces);
    }

    /**
     * @param string $html
     *
     * @return string
     */
    private static function cleanEmptyParagraphs(string $html): string
    {
        $pattern = "/<p[^>]*>(?:\s|&nbsp;)*<\/p>/";

        return Str::squish(preg_replace($pattern, '', $html));
    }

    /**
     * @param int $mode
     *
     * @return void
     * @throws Exception
     */
    private static function validateLinkProcessMode(int $mode): void
    {
        if (!in_array($mode, [
            self::PROCESS_LINK_MODE_RETURN_ERROR,
            self::PROCESS_LINK_MODE_EXTRACT_ANCHOR,
            self::PROCESS_LINK_MODE_IGNORE,
        ])) {
            throw new Exception('Непонятный режим обработки ссылок');
        }
    }
}