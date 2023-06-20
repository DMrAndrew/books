<?php namespace Books\Book\Jobs;

use Books\Book\Classes\FB2Manager;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Exception;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct(protected Edition $edition, protected File $fb2)
    {
        //
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        try {
            $tizisBook = (new FB2Manager($this->fb2))->apply();
            foreach ($tizisBook->getChapters() as $chapter) {
                (new Chapter())
                    ->service()
                    ->setEdition($this->edition)
                    ->from($chapter);
            }
            $this->edition->setHiddenStatus();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            $this->edition->setParsingFailed();
        }
    }
}
