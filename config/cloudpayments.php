<?php

return [
    'publicId' => env('CLOUDPAYMENTS_PUBLIC'),
    'apiSecret' => env('CLOUDPAYMENTS_SECRET'),
    'apiUrl' => 'https://api.cloudpayments.ru',
    'cultureName' => 'ru-RU', // https://cloudpayments.ru/Docs/Api#language
    'disable_payment_hmac_hash_validation' => env('CLOUDPAYMENTS_DISABLE_PAYMENT_HMAC_HASH_VALIDATION', false),
];
