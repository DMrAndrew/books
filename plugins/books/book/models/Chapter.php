<?php namespace Books\Book\Models;

use Model;
use October\Rain\Database\Traits\Sortable;
use October\Rain\Database\Traits\SoftDelete;
use October\Rain\Database\Traits\Validation;

/**
 * Chapter Model
 */
class Chapter extends Model
{
    use Sortable;
    use Validation;
    use SoftDelete;

    /**
     * @var string table associated with the model
     */
    public $table = 'books_book_chapters';

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['*'];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'title','book_id','content','published_at','length'
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [
        'title' => 'required|string',
        'book_id' => 'required|exists:books_book_books,id',
        'content' => 'string',
        'published_at' => 'date',
        'length' => 'integer',
    ];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = [];

    /**
     * @var array appends attributes to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * @var array hasOne and other relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function getStatusAttribute($status): ?ChapterStatus
    {
        return ChapterStatus::tryFrom($status);
    }

    public function setStatusAttribute(string|ChapterStatus $status)
    {
        $this->attributes['status'] = is_string($status) ? $status : $status->value;
    }
}
