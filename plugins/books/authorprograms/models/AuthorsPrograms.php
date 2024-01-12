<?php namespace Books\AuthorPrograms\Models;

use Books\AuthorPrograms\Classes\Enums\ProgramsEnums;
use Model;
use October\Rain\Database\Builder;

/**
 * AuthorsPrograms Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class AuthorsPrograms extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table name
     */
    public $table = 'books_authors_programs';

    protected $fillable = [
        'user_id',
        'program',
        'condition',
    ];

    /**
     * @var array rules for validation
     */
    public $rules = [];

    protected $casts = [
        'condition' => 'object'
    ];


    public function scopeUserProgramReaderBirthday(Builder $query)
    {
        $query->where('program', ProgramsEnums::READER_BIRTHDAY->value);
    }

    public function scopeUserProgramNewReader(Builder $query)
    {
        $query->where('program', ProgramsEnums::NEW_READER->value);
    }

    public function scopeUserProgramRegularReader(Builder $query)
    {
        $query->where('program', ProgramsEnums::REGULAR_READER->value);
    }
}
