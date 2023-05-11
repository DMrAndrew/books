<?php
declare(strict_types=1);

use Books\Payment\Controllers\PaymentController;

Route::get('payment', [PaymentController::class, 'index'])->name('payment.index');
Route::get('payment/charge/{order}', [PaymentController::class, 'charge'])->name('payment.charge');
Route::post('payment/service_webhook', [PaymentController::class, 'webhook'])->name('payment.service.webhook');
Route::get('payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
Route::get('payment/error/{order}', [PaymentController::class, 'error'])->name('payment.error');
