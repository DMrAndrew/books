<?php namespace Books\Videoblog\Models;

use App\traits\HasUserScope;
use Books\Profile\Models\Profile;
use Books\Profile\Models\Profiler;
use Books\Profile\Models\Subscriber;
use Books\User\Classes\PrivacySettingsEnum;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Models\Settings;
use Books\Videoblog\Classes\Enums\VideoBlogPostStatus;
use Exception;
use Illuminate\Support\Str;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use WordForm;

/**
 * Videoblog Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Videoblog extends Model
{
    use Validation;
    use SoftDelete;
    use HasUserScope;

    const TITLE_MAX_LENGTH = 60;
    const MAX_CREATE_SLUG_ATTEMPTS = 10;

    /**
     * @var string table name
     */
    public $table = 'books_videoblog_videoblogs';

    /**
     * @var array
     */
    public $rules = [
        'user_id' => 'nullable|integer|exists:users,id',
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'title' => 'required|string|max:' . self::TITLE_MAX_LENGTH,
        'slug' => ['string', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:books_videoblog_videoblogs,slug'],
        'link' => 'required|youtubelink',
        'content' => 'required',
        'published_at' => 'nullable|date',
    ];

    public $customMessages = [
        'title.required' => 'Добавьте пожалуйста заголовок к видеоблогу',
        'content.required' => 'Добавьте пожалуйста описание видеоблога',
        'link.required' =>  'Вставьте пожалуйста ссылку на видео',


    ];

    public $attributeNames = [
        'title' => 'Заголовок',
        'content' => 'Описание',
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'user_id',
        'profile_id',
        'status',
        'title',
        'content',
        'embed',
        'link',
        'published_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'published_at',
    ];

    protected $casts = [
        'status' => VideoBlogPostStatus::class,
    ];

    public $belongsTo = [
        'profile' => Profile::class
    ];

    public static array $endingArray = ['Видеоблог', 'Видеоблога', 'Видеоблогов'];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($post) {
            $post->generateSlugFromTitle();
        });

        static::deleting(function ($post) {
            $post->deleteComments();
        });
    }

    /**
     * Slug template: <id>-<slug-title>
     *
     * @return void
     * @throws Exception
     */
    public function generateSlugFromTitle(): void
    {
        $slug = Str::slug($this->attributes['title']);
        $postId = (int)(self::max('id'));

        for ($i = 0; $i < self::MAX_CREATE_SLUG_ATTEMPTS; $i++) {

            $uniqueSlug = sprintf("%s-%s", ++$postId, $slug);

            if (!static::query()->slug($uniqueSlug)->exists()) {
                $this->attributes['slug'] = $uniqueSlug;

                return;
            }
        }

        throw new Exception(sprintf('Не удалось сгенерировать уникальную ссылку для публикации в блоге после %s попыток', self::MAX_CREATE_SLUG_ATTEMPTS));
    }

    /**
     * @return void
     */
    public function deleteComments(): void
    {
        $this->comments()->delete();
    }


    public static function wordForm(): WordForm
    {
        return new WordForm(...self::$endingArray);
    }


    /**
     * @param Builder $builder
     * @param string $slug
     *
     * @return Builder
     */
    public function scopeSlug(Builder $builder, string $slug): Builder
    {
        return $builder->where('slug', $slug);
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', VideoBlogPostStatus::PUBLISHED);
    }

    /**
     * @param Builder $query
     * @param User|null $user
     *
     * @return Builder
     */
    public function scopePublicVisible(Builder $query, ?User $user = null): Builder
    {
        if (!$user) {
            $user = Auth::getUser();
        }

        $profileId = $user?->profile?->id;

        $postsTable = $this->getTable();
        $settingsTable = (new Settings())->getTable();
        $subscribersTable = (new Subscriber())->getTable();
        $profilersTable = (new Profiler())->getTable();

        $query->leftJoin($profilersTable, $profilersTable.'.master_id','=', $postsTable.'.profile_id');
        $query->leftJoin($settingsTable, $settingsTable.'.id','=', $profilersTable.'.slave_id');

        if ($profileId) {
            $query->leftJoin($subscribersTable, $postsTable.'.profile_id','=', $subscribersTable.'.profile_id');
        }

        $query
            ->select($postsTable.'.*')
            ->where($postsTable.'.status', VideoBlogPostStatus::PUBLISHED)

            ->where($profilersTable.'.master_type', Profile::class)
            ->where($profilersTable.'.slave_type', Settings::class)

            ->where(function($subQuery) use ($profileId, $settingsTable, $subscribersTable, $profilersTable) {

                /**
                 * For all
                 */
                $subQuery->where(function ($q) use ($settingsTable) {
                    return $q
                        ->where($settingsTable.'.type', UserSettingsEnum::PRIVACY_ALLOW_VIEW_BLOG)
                        ->where($settingsTable.'.value', PrivacySettingsEnum::ALL);
                });

                /**
                 * For subscribers
                 */
                if ($profileId) {
                    $subQuery->orWhere(function ($q) use ($subscribersTable, $settingsTable, $profileId) {
                        return $q
                            ->where($settingsTable.'.type', UserSettingsEnum::PRIVACY_ALLOW_VIEW_BLOG)
                            ->where($settingsTable.'.value', PrivacySettingsEnum::SUBSCRIBERS)
                            ->where($subscribersTable . '.subscriber_id', $profileId);
                    });
                }
            });

        return $query;
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePlanned(Builder $query): Builder
    {
        return $query->where('status', VideoBlogPostStatus::PLANNED);
    }
}
