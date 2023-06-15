<?php

namespace Books\Book\Jobs;

use Books\Book\Classes\WidgetService;
use Books\Book\Models\Chapter;
use Exception;
use Log;

class JobPaginate
{
    public function fire($job, $data)
    {
        try {
            Chapter::find($data['chapter_id'] ?? null)?->service()->paginate();
            $job->delete();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            throw $exception;
        }
    }
}
