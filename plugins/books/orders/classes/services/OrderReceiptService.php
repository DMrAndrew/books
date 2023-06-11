<?php
declare(strict_types=1);

namespace Books\Orders\Classes\Services;

use Books\Book\Models\Award;
use Books\Book\Models\Donation;
use Books\Book\Models\Edition;
use Books\Orders\Classes\Contracts\OrderReceiptService as OrderReceiptServiceContract;
use Books\Orders\Models\BalanceDeposit;
use Books\Orders\Models\Order as OrderModel;

class OrderReceiptService implements OrderReceiptServiceContract
{
    const MAX_PRODUCT_NAME_LENGTH = 128;

    private OrderModel $order;
    private OrderService $orderService;

    public function __construct(OrderModel $order)
    {
        $this->order = $order;
        $this->orderService = app(OrderService::class);
    }

    /**
     *
     * Данные для чека https://developers.cloudpayments.ru/#format-peredachi-dannyh-dlya-onlayn-cheka
     * {
            "Items": [//товарные позиции
                {
                    "label": "Наименование товара 1", //наименование товара
                    "price": 100.00, //цена
                    "quantity": 1.00, //количество
                    "amount": 100.00, //сумма
                    "vat": 0, //ставка НДС
                    "method": 0, // тег-1214 признак способа расчета - признак способа расчета
                    "object": 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                    "measurementUnit": "шт" //единица измерения
                }, {
                    "label": "Наименование товара 2", //наименование товара
                    "price": 200.00, //цена
                    "quantity": 2.00, //количество
                    "amount": 300.00, //сумма со скидкой 25%
                    "vat": 10, //ставка НДС
                    "method": 0, // тег-1214 признак способа расчета - признак способа расчета
                    "object": 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                    "measurementUnit": "шт", //единица измерения
                    "excise": 0.01, // тег-1229 сумма акциза
                    "countryOriginCode": "156", // тег-1230 цифровой код страны происхождения товара в соответствии с Общероссийским классификатором стран мира 3 симв.
                    "customsDeclarationNumber": "54180656/1345865/3435625/23", // тег-1231 регистрационный номер таможенной декларации 32 симв.
                    "ProductCodeData": //данные маркировки товара
                            {
                            "CodeProductNomenclature":"3031303239303030303033343....a78495a4f6672754744773d3d" //HEX представление штрих/бар кода маркировки целиком (Только для касс Микропэй)
                            }
                }, {
                        "label": "Наименование товара 3", //наименование товара
                        "price": 300.00, //цена
                        "quantity": 3.00, //количество
                        "amount": 900.00, //сумма
                        "vat": 20, //ставка НДС
                        "method": 0, // тег-1214 признак способа расчета - признак способа расчета
                        "object": 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
                        "measurementUnit": "шт", //единица измерения
                        "AgentSign": 6, //признак агента, тег ОФД 1057, 1222
                        "AgentData": { //данные агента, тег офд 1223
                            "AgentOperationName": null, // наименование операции банковского платежного агента или банковского платежного субагента, тег ОФД 1044
                            "PaymentAgentPhone": null,  // телефон платежного агента, тег ОФД 1073
                            "PaymentReceiverOperatorPhone": null, // телефон оператора по приему платежей, тег ОФД 1074
                            "TransferOperatorPhone": null, // телефон оператора перевода, тег ОФД 1075
                            "TransferOperatorName": null, // наименование оператора перевода, тег ОФД 1026
                            "TransferOperatorAddress": null, // адрес оператора перевода, тег ОФД 1005
                            "TransferOperatorInn": null // ИНН оператора перевода, тег ОФД 1016
                        },
                        "PurveyorData": { //данные поставщика платежного агента,  тег ОФД 1224
                            "Phone": "+74951234567", // телефон поставщика, тег ОД 1171
                            "Name": "ООО Ромашка", // наименование поставщика, тег ОФД 1225
                            "Inn": "1234567890" // ИНН поставщика, тег ОФД 1226
                        }
                    }
            ],
            //"calculationPlace": "www.my.ru", //место осуществления расчёта, по умолчанию берется значение из кассы
            //"taxationSystem": 0, //система налогообложения; необязательный, если у вас одна система налогообложения
            "email": "{{ paymentData.email }}", //e-mail покупателя, если нужно отправить письмо с чеком
            //"phone": "", //телефон покупателя в любом формате, если нужно отправить сообщение со ссылкой на чек
            "customerInfo": "{{ paymentData.data.userName}}", // тег-1227 Покупатель - наименование организации или фамилия, имя, отчество (при наличии), серия и номер паспорта покупателя (клиента)
            //"customerInn": "7708806063", // тег-1228 ИНН покупателя
            "isBso": false, //чек является бланком строгой отчётности
            "AgentSign": null, //признак агента, тег ОФД 1057
            "amounts":
            {
                "electronic": 1300.00, // Сумма оплаты электронными деньгами
                "advancePayment": 0.00, // Сумма из предоплаты (зачетом аванса) (2 знака после запятой)
                "credit": 0.00, // Сумма постоплатой(в кредит) (2 знака после запятой)
                "provision": 0.00 // Сумма оплаты встречным предоставлением (сертификаты, др. мат.ценности) (2 знака после запятой)
            }
        }
     * @return array
     */
    public function getReceiptData(): array
    {
        $receiptData = [];

        $receiptData['Items'] = $this->getOrderItems();

        $receiptData['email'] = $this->order->user->email;
        $receiptData['customerInfo'] = $this->order->user->username;
        $receiptData['isBso'] = false;
        $receiptData['AgentSign'] = null;

        $receiptData['amounts'] = $this->getAmountsData();

        return $receiptData;
    }

    /**
        "label": "Наименование товара 1", //наименование товара
        "price": 100.00, //цена
        "quantity": 1.00, //количество
        "amount": 100.00, //сумма
        "vat": 0, //ставка НДС
        "method": 0, // тег-1214 признак способа расчета - признак способа расчета
        "object": 0, // тег-1212 признак предмета расчета - признак предмета товара, работы, услуги, платежа, выплаты, иного предмета расчета
     *
     * @return array
     */
    private function getOrderItems(): array
    {
        /**
         * Товары
         */
        $items = $this->order->products->map(function ($product) {
            switch ($product->orderable_type) {
                case Edition::class:
                    $book = $product->orderable->book;

                    $label = "Издание '{$book->title}'";
                    $price = $product->initial_price;
                    $amount = $product->amount;

                    // скидка по промокоду
                    $this->order->promocodes->each(function ($orderPromocode) use ($book, &$amount) {
                        if ($orderPromocode->promocode->promoable->book->id == $book->id) {
                            $amount = 0;
                            return false; //break each loop
                        }
                    });
                break;

                case BalanceDeposit::class:
                    $label = 'Пополнение баланса';
                    $price = $amount = $product->amount;
                    break;

                case Donation::class:
                    $label = 'Поддержка автора';
                    $price = $amount = $product->amount;
                    break;

                case Award::class:
                    $label = "Награда '{$product->orderable->name}'";
                    $price = $amount = $product->amount;
                    break;
            }

            return [
                'id' => $product->id,
                "label" => mb_substr($label, 0, self::MAX_PRODUCT_NAME_LENGTH),
                "price" => $price,
                "quantity" => 1, //количество
                "amount" => $amount, //сумма
                "vat" =>  0, //ставка НДС
                "method" => 4, // признак способа расчета - https://developers.cloudkassir.ru/#method
                "object" => 0, // признак предмета расчета - https://developers.cloudkassir.ru/#object
                "measurementUnit" => "шт" //единица измерения
            ];
        })->toArray();

        /**
         * Промокоды
         */
        $promocodeItems = $this->order->promocodes->map(function ($orderPromocode) {

            $promocode = $orderPromocode->promocode;
            $label = "Скидка по промокоду '{$promocode->code}'";
            $amount = $promocode->promoable->priceTag()->price();

            return [
                'id' => $promocode->id,
                "label" => mb_substr($label, 0, self::MAX_PRODUCT_NAME_LENGTH),
                "price" => $amount,
                "quantity" => 1, //количество
                "amount" => 0, //сумма
                "vat" =>  0, //ставка НДС
                "method" => 4, // признак способа расчета - https://developers.cloudkassir.ru/#method
                "object" => 0, // признак предмета расчета - https://developers.cloudkassir.ru/#object
                "measurementUnit" => "шт" //единица измерения
            ];
        })->toArray();

        return array_merge($items, $promocodeItems);
    }

    /**
     "amounts":
        {
        "electronic": 1300.00, // Сумма оплаты электронными деньгами
        "advancePayment": 0.00, // Сумма из предоплаты (зачетом аванса) (2 знака после запятой)
        "credit": 0.00, // Сумма постоплатой(в кредит) (2 знака после запятой)
        "provision": 0.00 // Сумма оплаты встречным предоставлением (сертификаты, др. мат.ценности) (2 знака после запятой)
        }
     *
     * @return string[]
     */
    private function getAmountsData(): array
    {
        return [
            "electronic" => $this->orderService->calculateAmount($this->order),
            "advancePayment" => '0.00', // Сумма из предоплаты (зачетом аванса) (2 знака после запятой)
            "credit" => '0.00', // Сумма постоплатой(в кредит) (2 знака после запятой)
            "provision" => '0.00', // Сумма оплаты встречным предоставлением (сертификаты, др. мат.ценности) (2 знака после запятой)
        ];
    }
}
