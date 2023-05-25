<?php

namespace Books\Notifications\Classes;

use Books\Book\Models\Author;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Event;
use October\Rain\Support\Facades\Flash;
use RainLab\User\Facades\Auth;

trait NotificationHandlers
{
    public function onAcceptedCoAuthorInvite()
    {
        try {
            if (! Auth::getUser()) {
                throw new ApplicationException('Вы не авторизованы');
            }

            /** @var Author $author */
            $author = Author::query()
                ->where('book_id', post('book_id'))
                ->where('profile_id', Auth::getUser()?->profile?->getKey())
                ->firstOrFail();

            if (! empty($author->accepted)) {
                throw new ApplicationException('Соавторство уже принято');
            }

            $author->accepted = true;
            if ($author->save()) {
                Event::fire('books.book::author.accepted', [$author]);

                Flash::success('Вы успешно приняли соавторство');
            }
        } catch (\Exception $e) {
            Flash::error($e->getMessage());
        }
    }
}
