<?php namespace Books\Certificates\Models;

use Model;

/**
 * CertificateTransactions Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class CertificateTransactions extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_certificates_certificate_transactions';

    /**
     * @var array rules for validation
     */
    public $rules = [];
}
