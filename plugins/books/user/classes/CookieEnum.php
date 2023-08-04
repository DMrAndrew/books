<?php

namespace Books\User\Classes;

use Cookie;
use Exception;
use October\Rain\Database\Model;

enum CookieEnum: string
{
    case ADULT_ULID = 'adult_ulid';
    case FETCH_REQUIRED = 'fetch_required';
    case LOVED_GENRES = 'loved_genres';
    case UNLOVED_GENRES = 'unloved_genres';
    case RECOMMEND = 'recommend';

    case guest = 'guest_user';


    public function cast(): string
    {
        return match ($this) {
            self::FETCH_REQUIRED => 'bool',
            self::LOVED_GENRES, self::UNLOVED_GENRES, self::RECOMMEND, self::guest => 'object',
            default => 'string'
        };
    }

    /**
     * @throws Exception
     */
    public function make(mixed $value, ?int $TTL = null): \Symfony\Component\HttpFoundation\Cookie
    {
        $helper = $this->getHelper();
        $helper->setAttribute('value', $value);
        $args = [
            $this->value,
            $helper->attributes['value'], // raw
            $TTL
        ];
        return $TTL ? Cookie::make(...$args) : Cookie::forever(...$args);
    }

    /**
     * @throws Exception
     */
    public function set(mixed $value, ?int $TTL = null): void
    {
        Cookie::queue($this->make(...func_get_args()));
    }

    public function get()
    {
        $helper = $this->getHelper();
        $helper->setRawAttributes(['value' => Cookie::get($this->value)]);
        return $helper->getAttribute('value'); // cast
    }

    protected function getHelper(): CookieHelper
    {
        $helper = new CookieHelper();
        $helper->mergeCasts(['value' => $this->cast()]);
        return $helper;
    }

    public function forget(): void
    {
        Cookie::queue(Cookie::forget($this->value));
    }

}

class CookieHelper extends Model
{
    protected $fillable = ['value'];
}
