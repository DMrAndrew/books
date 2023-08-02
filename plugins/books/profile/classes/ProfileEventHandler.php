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
        if ($recipients = Backend\Models\UserGroup::where('code','owners')->first()?->users->map->email->toArray()) {
            $data = [
                'email' => $user->email,
                'old_username' => $user->profile->username,
                'new_username' => $user->profile->username_clipboard,
                'username_clipboard_comment' => $user->profile->username_clipboard_comment,
                'backend_url' => Backend::url("rainlab/user/users/preview/$user->id#primarytab-profili"),
            ];
            Mail::queue(
                'books.profile::mail.modify_username_request',
                $data,
                fn($msg) => $msg->to($recipients)
            );
        }
    }


}
