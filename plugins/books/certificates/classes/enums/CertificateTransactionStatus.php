<?php
declare(strict_types=1);

namespace Books\Certificates\Classes\Enums;

enum CertificateTransactionStatus: string
{
    const SENT = 'sent';
    const RECEIVED = 'received';
    const RETURNED = 'returned';
}
