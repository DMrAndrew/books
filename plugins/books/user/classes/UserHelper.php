<?php
declare(strict_types=1);

namespace Books\User\Classes;

use Illuminate\Contracts\Auth\Authenticatable;
use RainLab\User\Facades\Auth;

/**
 * Получаем пользователя один раз, чтобы не выполнять дублирующиеся запросы на получение пользователя/читателя
 * и его профилей каждый раз при вызове в разных местах и компонентах.
 * Следует использовать вместо Auth::getUser()
 */
class UserHelper
{
    public static function currentUser(): Authenticatable|null
    {
        $userGetter = GetUserAuthSingleton::getInstance();

        return $userGetter->getUser();
    }
}

class GetUserAuthSingleton
{
    protected static $_instance;
    private static ?Authenticatable $user;

    private function __construct() {}

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self;

            $user = Auth::getUser();

            $user?->load([
                'profiles',
                'profile',
                'ownedBooks'
            ]);

            self::$user = $user;
        }

        return self::$_instance;
    }

    public function getUser(): Authenticatable|null
    {
        return self::$user;
    }

    public function __clone() {}

    public function __wakeup() {}
}