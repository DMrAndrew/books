<?php namespace Books\Book\Jobs;

use Books\Book\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Paginate Job
 */
class Paginate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct(protected Chapter $chapter)
    {
        //
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        $this->chapter->service()->paginate();
    }
}
