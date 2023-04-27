<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;

/**
 * AdvertVisits Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class AdvertVisit extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_advert_visits';

    protected $fillable = ['ip', 'user_id', 'advert_id'];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public function scopeIp(Builder $builder, string $ip): Builder
    {
        return $builder->where('ip', $ip);
    }

    public function scopeUser(Builder $builder, ?User $user): Builder
    {
        return $builder->where('user_id', $user?->id);
    }

    public function scopeToday(Builder $builder): Builder
    {
        return $builder->whereDate('created_at', '=', today());
    }
}
