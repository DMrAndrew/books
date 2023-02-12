<?php

namespace Books\Book\Jobs;

use Books\Book\Models\Chapter;

class JobPaginate
{
    public function fire($job, $data)
    {
        try {
            Chapter::find($data['chapter_id'] ?? null)?->service()->paginate();
        } catch (\Exception $exception) {
            \Log::error($exception->getMessage());
        }
        $job->delete();
    }
}
