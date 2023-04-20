<?php namespace Books\Reposts\Models;

use Books\Book\Models\Book;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * Repost Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Repost extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_reposts_reposts';

    protected $fillable = ['user_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $morphTo = ['shareable' => []];


    protected static function booted()
    {
        static::addGlobalScope('orderByDesc', function (Builder $builder) {
            return $builder->orderByDesc('id');
        });
    }

    public function scopeType(Builder $builder, string $type): Builder
    {
        return $builder->where('shareable_type', '=', $type);
    }

    public function getLabel(): string
    {
        return match (get_class($this->shareable)) {
            Book::class => 'книгой ' . $this->shareable->title,
            default => 'неизвестным предметом.'
        };
    }

    public function getLink(): string
    {
        return match (get_class($this->shareable)) {
            Book::class => '/book-card/' . $this->shareable->id,
            default => '#'
        };
    }
}
