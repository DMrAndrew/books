<?php namespace Books\Book\Jobs;

use Books\Book\Classes\FB2Manager;
use Books\Book\Models\Edition;
use Bus;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use System\Models\File;

/**
 * ParseFB2 Job
 */
class ParseFB2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 240;

    /**
     * __construct a new job instance.
     */
    public function __construct(protected Edition $edition, protected File $file)
    {
        $this->onQueue('parsing');
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        try {

            $tizis = (new FB2Manager($this->file))->apply();
            $jobs = [];
            foreach (collect($tizis->getChapters())->values() as $key => $tizis) {
                $jobs[] = new ParseTizis(
                    $tizis,
                    $this->edition,
                    ['sort_order' => $key + 1]);
            }
            $id = $this->edition->id;
            $batch = Bus::batch($jobs)
                ->then(function (Batch $batch) use ($id) {
                    Edition::find($id)->setHiddenStatus();
                })
                ->catch(function (Batch $batch) use ($id) {
                    Edition::find($id)->setParsingFailed();
                })
                ->name('Загрузка книги: '.$this->edition->book->title)
                ->dispatch();


        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            $this->edition->setParsingFailed();
            $this->fail($exception->getMessage());
        }
    }

    public function tags(): array{
        return ['parsing','parseFB', get_class($this->edition).':'.$this->edition->id];
    }
}
