<?php

namespace Books\Book\Jobs;

use App\telegram\TChatsEnum;
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

    const NOTIFY_PROCESS_PERIOD = 4;

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
            ->unparent()
            ->broken()
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
        $total = $this->trackerQuery()->count();
        $this->notify(sprintf('%s found', $total));
        foreach ($this->trackerQuery()->cursor() as $item) {
            $total_deleted += $item->clearDuplicates();
            $total_processed++;
            $this->notifyProcess($total_deleted, $total_processed);
        }
        $template = collect([
            'End.',
            'Deleted: %s',
            'Processed: %s',
            'Total: %s',
        ])->join(PHP_EOL);

        $this->notify(sprintf($template, $total_deleted, $total_processed, $total));
    }

    public function removeUnnecessaryTrackers(): void
    {
        if (! Cache::has('unnecessary_trackers_removed')) {
            $builder = fn () => Tracker::query()->withoutTodayScope()->broken();
            $this->notify(sprintf('%s: deleted %s', Chapter::class, $builder()->type(Chapter::class)->delete()));
            $this->notify(sprintf('%s: deleted %s', Edition::class, $builder()->type(Edition::class)->delete()));
            Cache::set('unnecessary_trackers_removed', true);
        }
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
        $template = collect([
            '#',
            spl_object_id($this),
            PHP_EOL,
            $msg
        ]);
        return TChatsEnum::PERSONAL->make()->content($template->join(''))->send();
    }

    public function __destruct()
    {
        $this->notify(__METHOD__);
    }
}
