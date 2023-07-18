<?php namespace Books\Book\Models;

use Books\Book\Classes\BookUtilities;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use Books\Book\Classes\Enums\ContentTypeEnum;

/**
 * Content Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 *
 * @property string $body
 */
class Content extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_contents';

    protected $fillable = ['body', 'type'];
    /**
     * @var array rules for validation
     */
    public $rules = [
        'body' => 'nullable|string'
    ];

    public $morphTo = [
        'fillable' => []
    ];

    public function scopeNoType(Builder $builder)
    {
        return $builder->type(null);
    }

    public function scopeDeferred(Builder $builder)
    {
        return $builder->type(ContentTypeEnum::DEFERRED);
    }

    public function scopeType(Builder $builder, ?ContentTypeEnum $type): Builder
    {
        return $builder->where('type', '=', $type?->value);
    }

    public function getCleanBodyAttribute(): array|string|null
    {
        $str = $this->body;
        foreach (config('book.allowed_reader_domains') ?? [] as $domain) {
            $str = BookUtilities::removeDomainFromHtml($str, $domain);
        }
        return $str;
    }
}
