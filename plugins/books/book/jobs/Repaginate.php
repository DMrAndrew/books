<?php namespace Books\Book\Jobs;

use Books\Book\Classes\Enums\ChapterStatus;
use Books\Book\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Paginate Job
 */
class Repaginate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('paginate');
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        $chapters = Chapter::whereNull('next_id')
                ->whereNull('prev_id')
                ->where('status', ChapterStatus::PUBLISHED)
                ->where('sort_order', '!=', 1)
                ->get();

        $chapters->each(fn($chapter) => $chapter->service()->paginate());
    }

    public function tags(): array{
        return ['paginate', 'Repaginate'];
    }
}
