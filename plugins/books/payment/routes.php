<?php
declare(strict_types=1);

use Books\Payment\Controllers\PaymentController;

Route::post('payment/service_webhook_check', [PaymentController::class, 'check'])->name('payment.service.webhook.check');
Route::post('payment/service_webhook_pay', [PaymentController::class, 'pay'])->name('payment.service.webhook.pay');
Route::get('payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('payment/error/{order}', [PaymentController::class, 'error'])->name('payment.error');
