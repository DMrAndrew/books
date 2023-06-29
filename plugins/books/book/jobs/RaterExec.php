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
            $r = new Rater();
            $r->setBuilder($r->getBuilder()->whereIn('id', $this->data['ids']));
            $r->setWithDump($this->data['withDump']);
            $r->setDateBetween($this->data['dateBetween']);
            foreach ($this->data['closures'] as $closure) {
                $r->{$closure}();
            }
            $r->apply();

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }
    }
}
