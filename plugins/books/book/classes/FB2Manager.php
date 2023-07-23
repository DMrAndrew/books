<?php

namespace Books\Book\Classes;

use Exception;
use Log;
use System\Models\File;
use Tizis\FB2\FB2Controller;
use Tizis\FB2\Model\Book as TizisBook;
use Books\Book\Classes\Exceptions\FBParserException;

enum ParserMode: string
{
    case entity_decode = 'entity_decode';
    case inject_doctype = 'inject_doctype';
}

class FB2Manager
{
    protected FB2Controller $parser;
    protected ParserMode $mode = ParserMode::inject_doctype;

    public function __construct(protected File $fb2)
    {
    }

    /**
     * @param ParserMode $mode
     * @return FB2Manager
     */
    public function setMode(ParserMode $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @throws FBParserException
     */
    public function apply(): TizisBook
    {
        $file = file_get_contents($this->fb2->getLocalPath());

        try {
            $this->parser = new FB2Controller($this->prepareString($file));
            $this->parser->withNotes();
            $this->parser->startParse();
            if (!count($this->parser->getBook()->getChapters())) {
                $next = new static($this->fb2);
                return $next->setMode(ParserMode::entity_decode)->apply();
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw new FBParserException();
        }

        return $this->parser->getBook();
    }


    function prepareString(string $string): string
    {
        return match ($this->mode) {
            ParserMode::entity_decode => html_entity_decode($string, ENT_HTML5, 'UTF-8'),
            ParserMode::inject_doctype => $this->insertTextBeforeFictionBook($string, $this->convertedMnemonics()),
        };
    }

    /**
     * Inserts text at the beginning of an XML document, before the FictionBook tag.
     *
     * @param string $xml XML document string.
     * @param string $text Text to be inserted.
     *
     * @return string Modified XML string.
     */
    function insertTextBeforeFictionBook(string $xml, string $text): string
    {
        // Find the position of the first occurrence of '<FictionBook' in the XML string.
        $position = strpos($xml, '<FictionBook');

        // Insert the text at the found position if the tag is found.
        if (false !== $position) {
            $xml = substr_replace($xml, $text, $position, 0);
        }

        // Return the modified XML string.
        return $xml;
    }

    /**
     * Функция возвращает строку со всеми преобразованными мнемониками
     * @return string
     */
    function convertedMnemonics(): string
    {
        $mnemonics = [
            'nbsp' => '&#0160', //Неразрывный пробел
            'copy' => '&#0169', //Авторское право
            'amp' => '&#0038',
            'lt' => '&#0060', // Символ "Меньше"
            'gt' => '&#0062', // Символ "Больше"
            'quot' => '&#0034', // Кавычки
            'apos' => '&#0039', // Апостроф
            'cent' => '&#0162', // Символ цента
            'pound' => '&#0163', // Символ фунта
            'yen' => '&#0165', // Символ йены
            'euro' => '&#0128', // Символ евро
            'sect' => '&#0167', // Символ секции
            'reg' => '&#0174', // Зарегистрированный товарный знак
            'trade' => '&#0153', // Товарный знак
            'mdash' => '&#0151', // Длинное тире
            'ndash' => '&#0150', // Короткое тире
            'emsp' => '&#8195', // Широкий пробел
            'ensp' => '&#8194', // Узкий пробел
            'thinsp' => '&#8201', // Тонкий пробел
            'zwnj' => '&#8204', // Неразрывный пробел нулевой ширины
            'zwj' => '&#8205', // Разрывный пробел нулевой ширины
            'lrm' => '&#8206', // Маркер слева направо
            'rlm' => '&#8207', // Маркер справа налево
            'shy' => '&#0173', // Мягкий перенос строки
            'dagger' => '&#0134', // Крест
            'Dagger' => '&#0135', // Двойной крест
            'permil' => '&#0137', // Знак промилле
            'lsaquo' => '&#0139', // Однократная левая угловая кавычка
            'rsaquo' => '&#0155', // Однократная правая угловая кавычка
            'laquo' => '&#0171', // Левая двойная угловая кавычка
            'raquo' => '&#0187', // Правая двойная угловая кавычка
            'sbquo' => '&#0130', // Нижние одинарные кавычки
            'bdquo' => '&#0132', // Нижние двойные кавычки
            'circ' => '&#0710', // Модификационная буква циркумфлекса
            'tilde' => '&#0733', // Маленькая тильда
            'uuml' => '&#0220', // Маленькая тильда
            'lsquo' => '&#0145', // Левая одинарная кавычка
            'rsquo' => '&#0146', // Правая одинарная кавычка
            'ldquo' => '&#0147', // Левая двойная кавычка
            'rdquo' => '&#0148', // Правая двойная кавычка
            'bull' => '&#8226', // Маркер списка
            'hellip' => '&#0133', // Горизонтальная эллипса
            'prime' => '&#0823', // Прима
            'Prime' => '&#0824', // Двойная прима
            'oline' => '&#0826', // Перечеркивание
            'frasl' => '&#8260', // Дробная черта
            'weierp' => '&#8472', // Скриптовая заглавная P
            'image' => '&#8465', // Черный буквенный капитал I
            'real' => '&#8476', // Черный буквенный капитал R
            'alefsym' => '&#8501', // Символ алеф
            'larr' => '&#8592', // Стрелка влево
            'uarr' => '&#8593', // Стрелка вверх
            'rarr' => '&#8594', // Стрелка вправо
            'darr' => '&#8595', // Стрелка вниз
            'harr' => '&#8596', // Стрелка влево и вправо
            'crarr' => '&#8629', // Стрелка вниз, угол влево
            'lArr' => '&#8656', // Двойная стрелка влево
            'uArr' => '&#8657', // Двойная стрелка вверх
            'rArr' => '&#8658', // Двойная стрелка вправо
            'dArr' => '&#8659', // Двойная стрелка вниз
            'hArr' => '&#8660', // Двойная стрелка влево и вправо
            'forall' => '&#8704', // Для всех
            'part' => '&#8706', // Частичное дифференцирование
            'exist' => '&#8707', // Существует
            'empty' => '&#8709', // Пустое множество
            'nabla' => '&#8711', // Набла
            'isin' => '&#8712', // Элемент
            'notin' => '&#8713', // Не элемент
            'ni' => '&#8715', // Содержит в качестве члена
            'prod' => '&#8719', // N-составной продукт
            'sum' => '&#8721', // N-составная сумма
            'minus' => '&#8722', // Минус
            'lowast' => '&#8727', // Астериск
            'radic' => '&#8730', // Квадратный корень
            'prop' => '&#8733', // Пропорционально
            'infin' => '&#8734', // Бесконечность
            'ang' => '&#8736', // Угол
            'and' => '&#8743', // Логическое "и"
            'or' => '&#8744', // Логическое "или"
            'cap' => '&#8745', // Пересечение
            'cup' => '&#8746', // Объединение
            'int' => '&#8747', // Интеграл
            'there4' => '&#8756', // Поэтому
            'sim' => '&#8764', // Оператор тильды
            'cong' => '&#8773', // Конгруэнтно
            'asymp' => '&#8776', // Почти равно
            'ne' => '&#8800', // Не равно
            'equiv' => '&#8801', // Идентично
            'le' => '&#8804', // Меньше или равно
            'ge' => '&#8805', // Больше или равно
            'sub' => '&#8834', // Подмножество
            'sup' => '&#8835', // Суперсет
            'nsub' => '&#8836', // Не является подмножеством
            'sube' => '&#8838', // Подмножество или равно
            'supe' => '&#8839', // Суперсет или равно
            'oplus' => '&#8853', // Круглый плюс
            'otimes' => '&#8855', // Круглые времена
            'perp' => '&#8869', // Верхний штырь
            'sdot' => '&#8901', // Оператор точки
            'lceil' => '&#8968', // Левый потолок
            'rceil' => '&#8969', // Правый потолок
            'lfloor' => '&#8970', // Левый пол
            'rfloor' => '&#8971', // Правый пол
            'lang' => '&#9001', // Левая угловая скобка
            'rang' => '&#9002', // Правая угловая скобка
            'loz' => '&#9674', // Ромб
            'spades' => '&#9824', // Черный пиковый костюм
            'clubs' => '&#9827', // Черный трефовый костюм
            'hearts' => '&#9829', // Черный червовый костюм
            'diams' => '&#9830', // Черный бубновый костюм
        ];

        $xml = '<!DOCTYPE naughtyxml [';
        foreach ($mnemonics as $mnemonic => $code) {
            $xml .= "<!ENTITY $mnemonic \"$code;\"> ";
        }
        $xml .= ']>';

        return $xml;
    }}
