<?php

namespace Books\Book\Jobs;

use Exception;
use Log;
use RainLab\User\Models\User;
use Books\Book\Models\Chapter;

class JobProgress
{
    /**
     * @throws Exception
     */
    public function fire($job, $data): void
    {
        try {
            $chapter = Chapter::find($data['chapter_id'] ?? null);
            if (!$chapter) {
                throw new Exception(__CLASS__ . ' Chapter required');
            }

            $user = User::find($data['user_id'] ?? null);
            $chapter->computeProgress($user);
            $chapter->edition->computeProgress($user);
//            $chapter->edition->book->rater()->applyStatsAll()->apply();
            $job->delete();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }


    }
}
