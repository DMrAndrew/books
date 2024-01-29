<?php namespace Books\Book\Jobs;

use Books\Book\Classes\Exceptions\UnknownFormatException;
use Books\Book\Models\Edition;
use Books\Book\Models\Chapter as BookChapter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use Tizis\FB2\Model\Chapter as TizisChapter;

/**
 * ParseTizis Job
 */
class ParseTizis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * __construct a new job instance.
     */
    public function __construct(public TizisChapter $tizis,
                                public Edition      $edition,
                                public array        $payload = []
    )
    {
        $this->onQueue('parsing');
    }


    /**
     * handle the job.
     * @throws UnknownFormatException
     */
    public function handle(): void
    {
        try {
            if ($this->payload['sort_order'] ?? false) {
                if ($exist = $this->edition->chapters()->sortOrder($this->payload['sort_order'])->first()) {
                    $exist->delete();
                }
            }
            (new BookChapter($this->payload))->service()
                ->setEdition($this->edition)
                ->from($this->tizis);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            $this->batch()->cancel();
            $this->fail($exception);
        }
    }

    public function tags(): array{
        return ['parsing','parseTizisChapter', get_class($this->edition).':'.$this->edition->id];
    }
}
