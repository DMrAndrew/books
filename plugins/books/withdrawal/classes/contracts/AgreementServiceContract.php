<?php
declare(strict_types=1);

namespace Books\Withdrawal\Classes\Contracts;

interface AgreementServiceContract
{
    public function getAgreementHTML(bool $verified = false): string;

    public function getAgreementPDF(): string;

    public function sendVerificationCode(): void;

    public function verifyAgreement(string $code): bool;
}
