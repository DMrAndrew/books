<?php namespace Books\Book\Jobs;

use Books\Book\Models\Tracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Reading Job
 */
class Reading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct(public Tracker $tracker)
    {
        $this->onQueue('reading');
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        try {
            $this->tracker->afterTrack();
        }
        catch (\Exception $exception){
            $this->fail($exception->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['reading',( $this->tracker->trackable ? get_class($this->tracker->trackable) : '').':'.$this->tracker->trackable?->id, $this->tracker->user_id ? $this->tracker->user_id:$this->tracker->ip];
    }
}
