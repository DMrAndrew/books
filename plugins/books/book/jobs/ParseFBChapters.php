<?php

namespace Books\Book\Jobs;

use Books\Book\Classes\FB2Manager;
use Books\Book\Models\Chapter;
use Books\Book\Models\Edition;
use Exception;
use Log;
use System\Models\File;

class ParseFBChapters
{
    public function fire($job, $data)
    {
        $edition = Edition::find($data['edition_id']);
        if (!$edition) {
            throw new Exception('Edition required.');
        }

        $file = File::find($data['file_id']);
        if (!$file) {
            $edition->setParsingFailed();
            throw new Exception('File required.');
        }
        try {

            if ($edition && $file) {
                $tizisBook = (new FB2Manager($file))->apply();
                foreach ($tizisBook->getChapters() as $chapter) {
                    (new Chapter())->service()->setEdition($edition)->from($chapter);
                }
                $edition->setHiddenStatus();
            }
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            $edition->setParsingFailed();
        }
        $job->delete();
    }
}
