<?php

namespace Books\Book\Jobs;

use App\telegram\TChatsEnum;
use Books\Book\Models\Book;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Books\Book\Models\Pagination;
use Books\Book\Models\Tracker;
use Cache;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JsonException;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use Throwable;

/**
 * ClearTrackers Job
 */
class ClearTrackers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?Carbon $last_send = null;

    const NOTIFY_PROCESS_PERIOD = 2;

    /**
     * __construct a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('clearTrackers');
        $this->notify(__METHOD__);
    }

    public function lastSend(): \Illuminate\Support\Carbon|Carbon
    {
        $this->last_send ??= now();

        return $this->last_send;
    }

    public function trackerQuery()
    {
        return Tracker::query()
            ->withoutTodayScope()
            ->type(Pagination::class)
            ->where('time', 0)
            ->orderBy('created_at');
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        $this->removeUnnecessaryTrackers();
        $total_deleted = 0;
        $total_processed = 0;
        $processed = $this->processed();
        $total = $this->trackerQuery()->get()->filter(fn ($i) => ! in_array($i->id, $processed))->count();
        unset($processed);
        $this->notify(sprintf('%s found', $total));
        foreach ($this->trackerQuery()->cursor() as $item) {
            $processed = $this->processed();
            if (in_array($item->id, $processed)) {
                continue;
            }

            $this->saveProcessed(array_merge([$item->id], $processed));
            $total_deleted += $item->clearDuplicates();
            $total_processed++;
            $this->notifyProcess($total_deleted, $total_processed);
        }
        $this->notify(sprintf('End.'.PHP_EOL.'Deleted: %s'.PHP_EOL.'Processed: %s'.PHP_EOL.'Total: %s', $total_deleted, $total_processed, $total));
    }

    public function removeUnnecessaryTrackers(): void
    {
        if (! Cache::has('unnecessary_trackers_removed')) {

            $this->notify(sprintf('%s: deleted %s', Chapter::class, Tracker::query()->withoutTodayScope()->type(Chapter::class)->delete()));
            $this->notify(sprintf('%s: deleted %s', Edition::class, Tracker::query()->withoutTodayScope()->type(Edition::class)->delete()));
        }
        Cache::set('unnecessary_trackers_removed', true);
    }

    public function notifyProcess(int $deleted, int $processed)
    {
        if (abs($this->lastSend()->diffInMinutes(now())) >= self::NOTIFY_PROCESS_PERIOD) {
            $this->notify(sprintf('%s deleted.'.PHP_EOL.'%s processed.', $deleted, $processed));
            $this->last_send = now();
        }

    }

    public function failed(Throwable $exception)
    {
        $this->notify(__METHOD__.PHP_EOL.$exception->getMessage());
    }

    /**
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    private function notify(string $msg)
    {
        return TChatsEnum::PERSONAL->make()->content('#'.spl_object_id($this).PHP_EOL.$msg)->send();
    }

    public function processed()
    {
        return Cache::get('cleared_trackers') ?? [];
    }

    public function saveProcessed(array $array): void
    {
        Cache::set('cleared_trackers', $array);
    }

    public function __destruct()
    {
        $this->notify(__METHOD__);
    }
}
