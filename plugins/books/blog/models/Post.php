<?php namespace Books\Blog\Models;

use App\traits\HasUserScope;
use Books\Blog\Classes\Enums\PostStatus;
use Books\Profile\Models\Profile;
use Books\Profile\Models\Subscriber;
use Books\User\Classes\PrivacySettingsEnum;
use Books\User\Classes\UserSettingsEnum;
use Books\User\Models\Settings;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Facades\Auth;
use System\Models\File;
use WordForm;

/**
 * Blog Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Post extends Model
{
    use Validation;
    use SoftDelete;
    use HasUserScope;

    const TITLE_MAX_LENGTH = 60;
    const PREVIEW_MAX_LENGTH = 500;
    const MAX_CREATE_SLUG_ATTEMPTS = 10;
    const AVAILABLE_IMAGE_EXTENSIONS = ['jpeg', 'jpg', 'png'];
    const MAX_IMAGE_SIZE_MB = 3;

    /**
     * @var string table name
     */
    public $table = 'books_blog_posts';

    /**
     * @var array
     */
    public $rules = [
        'user_id' => 'nullable|integer|exists:users,id',
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'title'   => 'required|string|max:' . self::TITLE_MAX_LENGTH,
        'slug'    => ['string', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:books_blog_posts,slug'],
        'preview' => 'string',
        'content' => 'required',
        'published_at' => 'nullable|date',
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
        'published_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'published_at',
    ];

    protected $casts = [
        'status' => PostStatus::class,
    ];

    public $belongsTo = [
        'profile' => Profile::class
    ];

    public $attachMany = [
        'content_images' => File::class,
    ];

    public static array $endingArray = ['Блог', 'Блога', 'Блогов'];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($post) {
            $post->generateSlugFromTitle();
        });

        static::saving(function ($post) {
            $post->fillPreviewFromContent();
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

            $uniqueSlug = $postId . '-' . $slug;

            if (!static::query()->slug($uniqueSlug)->exists()) {
                $this->attributes['slug'] = $uniqueSlug;

                return;
            } else {
                $postId++;
            }
        }

        throw new Exception('Не удалось сгенерировать уникальную ссылку для публикации в блоге после ' . self::MAX_CREATE_SLUG_ATTEMPTS . ' попыток');
    }

    /**
     * @return void
     */
    public function deleteComments(): void
    {
        $this->comments()->delete();
    }

    /**
     * @return void
     */
    public function fillPreviewFromContent(): void
    {
        $this->attributes['preview'] = substr(strip_tags($this->attributes['content']), 0, self::PREVIEW_MAX_LENGTH);
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
        return $query->where('status', PostStatus::PUBLISHED);
    }

    /**
     * @param Builder $query
     * @param Auth|null $user
     *
     * @return Builder
     */
    public function scopePublicVisible(Builder $query, ?Auth $user = null): Builder
    {
        if (!$user) {
            $user = Auth::getUser();
        }

        $profileId = $user?->profile?->id;

        $settingsTable = (new Settings())->getTable();
        $postsTable = $this->getTable();
        $subscribersTable = (new Subscriber())->getTable();

        $query->leftJoin($settingsTable, $postsTable.'.user_id','=', $settingsTable.'.user_id');
        $query->leftJoin($subscribersTable, $postsTable.'.profile_id','=', $subscribersTable.'.profile_id');

        $query
            ->select($postsTable.'.*')
            ->where(function($subQuery) use ($profileId, $settingsTable, $postsTable, $subscribersTable) {

                /**
                 * Except posts that `Nobody can see`
                 */
                $subQuery->where(function ($q) use ($settingsTable) {
                    return $q
                        ->published()
                        ->where(function ($query) use ($settingsTable) {
                            $query
                                ->whereNull($settingsTable.'.user_id')
                                ->orWhere(function($query) use ($settingsTable) {
                                    return $query
                                        ->where($settingsTable.'.type', UserSettingsEnum::PRIVACY_ALLOW_VIEW_BLOG)
                                        ->whereNot($settingsTable.'.value', PrivacySettingsEnum::NONE);
                                });
                        });
                });

                /**
                 * For subscribers
                 */
                if ($profileId) {
                    $subQuery->where(function ($q) use ($subscribersTable, $settingsTable, $profileId) {
                        return $q
                            ->published()
                            ->where(function ($query) use ($settingsTable) {
                                $query
                                    ->whereNull($settingsTable.'.user_id')
                                    ->orWhere(function($query) use ($settingsTable) {
                                        return $query
                                            ->where($settingsTable.'.type', UserSettingsEnum::PRIVACY_ALLOW_VIEW_BLOG)
                                            ->where($settingsTable.'.value', PrivacySettingsEnum::SUBSCRIBERS);
                                    });
                            })
                            ->where($subscribersTable . '.subscriber_id', $profileId);
                    });
                }

                /**
                 * For all
                 */
                $subQuery->orWhere(function ($q) use ($subscribersTable, $settingsTable, $profileId) {
                    return $q
                        ->published()
                        ->where(function ($query) use ($settingsTable) {
                            $query
                                ->whereNull($settingsTable.'.user_id')
                                ->orWhere(function($query) use ($settingsTable) {
                                    return $query
                                        ->where($settingsTable.'.type', UserSettingsEnum::PRIVACY_ALLOW_VIEW_BLOG)
                                        ->where($settingsTable.'.value', PrivacySettingsEnum::ALL);
                                });
                        });
                });

                /**
                 * Author is current profile
                 */
                if ($profileId) {
                    $subQuery->orWhere($postsTable.'.profile_id', $profileId);
                }
        });

        return $query;
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function scopePLanned(Builder $query): Builder
    {
        return $query->where('status', PostStatus::PLANNED);
    }
}
