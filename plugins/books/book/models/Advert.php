<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use System\Models\File;
use ValidationException;

/**
 * Advert Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 *
 * @method HasMany visits
 *
 */
class Advert extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_adverts';

    protected $fillable = ['enabled', 'allowed_visit_count'];

    /**
     * @var array rules for validation
     */
    public $rules = [];


    public $hasMany = [
        'visits' => [AdvertVisit::class, 'key' => 'advert_id', 'otherKey' => 'id']
    ];

    public $belongsTo = [
        'book' => [Book::class]
    ];

    public $attachOne = [
        'banner' => [File::class]
    ];

    public function scopeEnabled(Builder $builder): Builder
    {
        return $builder->where('enabled', true);
    }

    public function scopeAllowed(Builder $builder): Builder
    {
        return $builder->where('allowed_visit_count', '>', 0);
    }

    public function toggleState(): static
    {
        $this->enabled = !$this->enabled;
        return $this;
    }

    public function registerVisit(?User $user = null, ?string $ip = null): ?AdvertVisit
    {
        if (!$this->getOriginal('enabled') || $this->visits()->count() >= $this->allowed_visit_count) {
            return null;
        }
        $ip ??= request()->ip();
        $user ??= Auth::getUser();
        if (!$user && !$ip) {
            throw new ValidationException(['user' => 'User or IP required']);
        }
        $builder = fn() => $this->visits()->today()->where(
            fn($b) => $b->when($user, fn($q) => $q->user($user))->when($ip, fn($i) => $i->orWhere(fn($orWhere) => $orWhere->ip($ip)))
        );

        return $builder()->first() ?? $this->visits()->create(['user_id' => $user->id, 'ip' => $ip]);
    }


}
