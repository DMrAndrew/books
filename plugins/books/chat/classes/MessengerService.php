<?php

namespace Books\Chat\Classes;

use Cms\Classes\Controller;
use Cms\Helpers\Component;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use RainLab\User\Facades\Auth;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Events\ParticipantReadEvent;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Http\Collections\ThreadCollection;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ParticipantRepository;

class MessengerService
{
    protected Messenger $messenger;

    public function __construct(protected ?MessengerProvider $provider)
    {
        $this->provider ??= Auth::getUser()?->profile;
        if (!$this->provider) {
            return;
        }
        $this->messenger = app(Messenger::class);
        $this->messenger->setProvider($this->provider);
    }

    public function headerArray(): array
    {
        if (!$this->provider) {
            return [];
        }
        return [
            'unread_thread_count' => $this->unreadThreadCount(),
            'threads' => $this->unreadThreads()
        ];
    }

    private function unreadThreadCount()
    {
        return $this->unreadBuilder()->count();
    }

    private function unreadThreads()
    {
        return (new ThreadCollection(
            $this->unreadBuilder()
                ->latest('updated_at')
                ->with([
                    'participants.owner',
                    'latestMessage.owner',
                    'activeCall.participants.owner'
                ])
                ->orderBy('updated_at')
                ->limit(4)
                ->get()
        ))->toArray(request())['data'];
    }

    public function unreadBuilder(): Thread|Builder
    {
        return $this->threadBuilder()
            ->where(function (Builder $query) {
                return $query->whereNull('participants.last_read')
                    ->orWhere('threads.updated_at', '>', $query->raw('participants.last_read'));
            });
    }

    private function threadBuilder(): Thread|Builder
    {
        return Thread::hasProvider($this->messenger->getProvider());
    }

    public function render(): array
    {
        $path = plugins_path('books/chat/components/messenger/');
        $templates = [$path.'threads.htm', $path.'unread_thread_count.htm'];
        foreach ($templates as $template) {
            if (!file_exists($template) || !is_readable($template)) {
                return [];
            }
        }


        $twig = (new Controller())->getTwig();
        $data = $this->headerArray();

        [$threads, $unread_count] = collect($templates)->map(fn($t) => file_get_contents($t))->toArray();
        return [
            'thread_spawn' => $twig->createTemplate($threads)->render($data),
            'unread_thread_count_spawn' => $twig->createTemplate($unread_count)->render($data)
        ];
    }

    public static function broadcastUpdate(
        NewMessageEvent|ThreadArchivedEvent|NewThreadEvent|ParticipantReadEvent $event
    ): void {
        $participantRepository = new ParticipantRepository(\messenger());
        $participants = match (get_class($event)) {
            NewMessageEvent::class, ThreadArchivedEvent::class, NewThreadEvent::class
            => ($participantRepository->getThreadBroadcastableParticipants($event->thread,
                true))->load('owner'),
            ParticipantReadEvent::class => [$event->participant]
        };

        Log::info($participants);
        foreach ($participants as $participant) {
            MessengerUpdatedEvent::dispatch($participant->owner->withoutRelations());
        }
    }

    public static function updatableEvents() :array
    {
        return [
            NewMessageEvent::class,
            ThreadArchivedEvent::class,
            NewThreadEvent::class,
            ParticipantReadEvent::class
        ];
    }
}