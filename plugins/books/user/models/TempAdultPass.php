<?php

namespace Books\User\Models;

use App\traits\HasIPScope;
use Books\User\Classes\CookieEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Prunable;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;

/**
 * TempAdultPass Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class TempAdultPass extends Model
{
    use Validation;
    use HasUlids;
    use HasIPScope;
    use Prunable;

    /**
     * @var string table name
     */
    public $table = 'books_user_temp_adult_passes';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $incrementing = false;

    protected $fillable = ['ip', 'is_agree'];

    protected $dates = ['expire_in'];

    public function scopeAgree(Builder $builder): Builder
    {
        return $builder->where('is_agree', '=', true);
    }

    public function scopeULID(Builder $builder, string $ULID): Builder
    {
        return $builder->where('id', $ULID);
    }

    public function scopeFindByCredential(Builder $builder, string $ip, ?string $ULID): Builder
    {
        return $builder->where(fn ($b) => $b->ip($ip)
            ->when($ULID, fn ($when) => $when->orWhere(fn ($i) => $i->ULID($ULID))));
    }

    public static function lookUp()
    {
        return static::query()->findByCredential(request()->ip(), CookieEnum::ADULT_ULID->get());
    }

    public static function make($attributes = [])
    {
        return parent::make(array_merge([
            'ip' => request()->ip(),
        ], $attributes));
    }

    protected function beforeCreate()
    {
        $this->expire_in = Carbon::now()->copy()->addDay();
    }

    /**
     * @throws Exception
     */
    protected function afterCreate()
    {
        CookieEnum::ADULT_ULID->set($this->id); //queue cookie
    }

    protected function beforeSave()
    {
        if ($this->exists && $this->isDirty('expire_in')) {
            $this->expire_in = $this->getOriginal('expire_in');
        }
    }

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->whereDate('expire_in', '>=', Carbon::now());
    }

    public function isActive(): bool
    {
        return $this->expire_in->gt(Carbon::now());
    }

    public function prunable()
    {
        return static::query()->whereDate('created_at', '<', today());
    }
}
