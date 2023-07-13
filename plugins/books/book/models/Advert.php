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
    const DEFAULT_ALLOWED_VISITS_COUNT = 150;

    /**
     * @var string table name
     */
    public $table = 'books_book_adverts';

    protected $fillable = ['enabled', 'allowed_visit_count'];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'banner' => 'nullable|image|mimes:jpg,jpeg,png,gif|dimensions:min_width=272,min_height=112|max:4096'
    ];


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

    public function registerVisit(): ?AdvertVisit
    {
        if (!$this->getOriginal('enabled') || $this->visits()->count() >= $this->getOriginal('allowed_visit_count')) {
            return null;
        }

        if ($visit = $this->visits()->today()->userOrIpWithDefault()->first()) {
            return $visit;
        }

        return $this->visits()->create([
            'user_id' => Auth::getUser()?->id,
            'ip' => request()->ip()
        ]);
    }


}
