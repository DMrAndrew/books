<?php

namespace Books\Profile\Classes;

use Backend;
use Backend\Models\User as BackendUser;
use Mail;

class ProfileEventHandler
{
    /**
     * @param $user
     * @return void
     */
    public function usernameModifyRequested($user): void
    {
        $data = [
            'email' => $user->email,
            'old_username' => $user->profile->username,
            'new_username' => $user->profile->username_clipboard,
            'username_clipboard_comment' => $user->profile->username_clipboard_comment,
            'backend_url' => Backend::url("rainlab/user/users/preview/$user->id#primarytab-profili"),
        ];

        //TODO Отправлять админу
        if ($recipient = BackendUser::first()?->email) {
            Mail::queue(
                'books.profile::mail.modify_username_request',
                $data,
                fn ($msg) => $msg->to($recipient)
            );
        }
    }

    public function createdProfilableModel($model): void
    {
        $profiler = $model->profiler();
        $profiler->update(['ids' => array_merge($profiler->getIds(), [$model->id])]);
    }

    public function deletedProfilableModel($model): void
    {
        $profiler = $model->profiler();
        $profiler->update(['ids' => array_values(array_diff($profiler->getIds(), [$model->id]))]);
    }
}
