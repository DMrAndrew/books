<?php namespace Books\Book\Jobs;

use Books\Book\Classes\Rater;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

/**
 * RaterExec Job
 */
class RaterExec implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct(protected array $data)
    {
        $this->onQueue('compute');
    }

    /**
     * handle the job.
     * @throws Exception
     */
    public function handle(): void
    {
        try {

            Rater::make($this->data)->run();

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }
    }

    public function tags(): array
    {
        return ['compute', 'RaterExec'];
    }
}
