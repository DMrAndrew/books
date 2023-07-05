<?php namespace Books\Book\Jobs;

use Books\Book\Classes\GenreRater;
use Books\Book\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * GenreRaterExec Job
 */
class GenreRaterExec implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('compute');
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        (new Book())->rater()->setWithDump(true)->applyAllStats()->run();
        (new GenreRater())->compute();
    }

    public function tags(): array{
        return ['compute', 'GenreRaterExec'];
    }
}
