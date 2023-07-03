<?php namespace Books\Book\Models;

use Books\Notifications\Classes\NotificationTypeEnum;
use Event;
use Model;
use October\Rain\Database\Traits\Validation;
use RainLab\Notify\Models\Notification;

/**
 * SystemMessage Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 *
 * @property string name
 * @property string text
 */
class SystemMessage extends Model
{
    use Validation;

    /**
     * @var string table name
     */
    public $table = 'books_book_system_messages';


    protected $fillable = ['text', 'name'];
    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|string',
        'text' => 'required|string',
    ];

    protected function afterCreate()
    {
        Event::fire('system::message', [$this]);
    }

    protected function afterUpdate()
    {
        if ($this->isDirty('text')) {
            $this->updateStoredTextQueue();
        }
    }

    protected function beforeDelete()
    {
        $this->queryRecipients()->get()->each->delete();
    }

    public function updateStoredTextQueue()
    {
        $id = $this->id;
        dispatch(function () use ($id) {
            static::find($id)?->updateStoredText();
        });
    }

    public function getTextPreviewAttribute()
    {
        return $this->text;
    }

    public function updateStoredText()
    {
        $this->queryRecipients()->get()->each(function ($notification) {
            $notification->update(['data' => array_merge($notification->data, ['text' => $this->text])]);
        });

    }

    public function queryRecipients(): \Illuminate\Database\Eloquent\Builder
    {
        return Notification::query()->where('type', NotificationTypeEnum::SYSTEM->value)
            ->where('data->message_id', $this->id);
    }

}
