<?php namespace Books\Book\Jobs;

use Books\Book\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RainLab\User\Models\User;

/**
 * Reading Job
 */
class Reading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct(public Chapter $chapter, public ?User $user = null)
    {
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        $this->chapter->computeProgress($this->user);
        $this->chapter->edition->computeProgress($this->user);
    }
}
