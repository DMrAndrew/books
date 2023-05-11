<?php

use Books\Payment\Classes\PaymentService;

//Route::get('payment/charge', [PaymentService::class, 'charge'])->name('payment.charge');
Route::post('payment/service_webhook', [PaymentService::class, 'webhook'])->name('payment.service.webhook');
Route::get('payment/success', [PaymentService::class, 'success'])->name('payment.success');
Route::get('payment/error', [PaymentService::class, 'error'])->name('payment.error');
