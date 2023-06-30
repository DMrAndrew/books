<?php namespace Books\Notifications\Jobs;

use Books\Book\Models\Discount;
use Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

/**
 * Paginate Job
 */
class DiscountNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * __construct a new job instance.
     */
    public function __construct(protected Discount $discount)
    {
        $this->onQueue('default');
    }

    /**
     * handle the job.
     */
    public function handle(): void
    {
        Event::fire('books.book::edition.discounted', [$this->discount]);
    }
}
