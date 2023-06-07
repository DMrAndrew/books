<?php
declare(strict_types=1);

use Books\Payment\Controllers\PaymentController;

Route::post('payment/service_webhook', [PaymentController::class, 'webhook'])->name('payment.service.webhook');
Route::get('payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('payment/error/{order}', [PaymentController::class, 'error'])->name('payment.error');
