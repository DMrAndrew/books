<?php namespace Books\Blog\Models;

use Books\Profile\Models\Profile;
use Exception;
use Illuminate\Support\Str;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Database\Traits\Validation;
use System\Models\File;

/**
 * Blog Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class Post extends Model
{
    use Validation;

    const TITLE_MAX_LENGTH = 60;
    const PREVIEW_MAX_LENGTH = 200;
    const MAX_CREATE_SLUG_ATTEMPTS = 10;

    /**
     * @var string table name
     */
    public $table = 'books_blog_posts';

    /**
     * @var array
     */
    public $rules = [
        'profile_id' => 'required|exists:books_profile_profiles,id',
        'title'   => 'required|string|max:' . self::TITLE_MAX_LENGTH,
        'slug'    => ['string', 'regex:/^[a-z0-9\/\:_\-\*\[\]\+\?\|]*$/i', 'unique:books_blog_posts,slug'],
        'preview' => 'string',
        'content' => 'required',
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'profile_id',
        'title',
        'content',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public $belongsTo = [
        'profile' => Profile::class
    ];

    public $attachMany = [
        'pictures' => File::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($post) {
            $post->generateSlugFromTitle();
        });

        static::saving(function ($post) {
            $post->fillPreviewFromContent();
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
        $postId = Post::orderByDesc('id')->first()?->id + 1 ?? 1;

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
    public function fillPreviewFromContent(): void
    {
        $this->attributes['preview'] = substr(strip_tags($this->attributes['content']), 0, self::PREVIEW_MAX_LENGTH);
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
}