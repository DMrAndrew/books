<?php namespace Books\Certificates\Models;

use Books\Certificates\Classes\Enums\CertificateTransactionStatus;
use Books\Profile\Models\Profile;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use System\Models\File;

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

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'amount',
        'description',
        'anonymity',
        'status',
        'image',
    ];

    /**
     * @var array
     */
    public $rules = [
        'recipient_id' => 'required',
        'amount' => 'required',
        'description' => 'required',
        ];

    public $customMessages = [
        'recipient_id.required' => 'Выберите получателя',
        'amount.required' => 'Введите сумму',
        'description.required' =>  'Напишите сообщение',


    ];

    public $attributeNames = [
        'recipient_id' => 'Имя получателя',
        'amount' => 'Сумма перевода',
        'description' => 'Текст',
    ];

    public function scopeNotAcceptedCertificates(Builder $q)
    {
        return $q->where('status', CertificateTransactionStatus::SENT);
    }

    public $belongsTo = [
        'receiver' => [Profile::class, 'key' => 'recipient_id', 'otherKey' => 'id'],
        'sender' => [Profile::class, 'key' => 'sender_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'certificate_image' => File::class,
    ];
}
