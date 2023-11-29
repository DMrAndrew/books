<?php

namespace Books\Moderation\Facades;

use Books\Moderation\Classes\LaravelDrafts as LaravelDraftsClass;
use October\Rain\Support\Facade;

/**
 * @method static \Illuminate\Contracts\Auth\Authenticatable getCurrentUser()
 * @method static void previewMode(bool $previewMode = true)
 * @method static void disablePreviewMode()
 * @method static bool isPreviewModeEnabled()
 * @method static void withDrafts(bool $withDrafts = true)
 * @method static bool isWithDraftsEnabled()
 *
 * @see \Books\Moderation\Classes\LaravelDrafts
 */
class LaravelDrafts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return LaravelDraftsClass::class;
    }
}
