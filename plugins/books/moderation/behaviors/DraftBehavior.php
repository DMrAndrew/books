<?php

namespace Books\Moderation\Behaviors;

use Event;
use Model;
use October\Rain\Database\ModelBehavior;

class DraftBehavior extends ModelBehavior
{

    public function registerSavedOnNewRevision()
    {
        Event::listen('model.afterSaveNewRevision', function(Model $model, Model $revision) {
            if ($model->isNot($this)) {
                return;
            }

            $revision->created_at = $this->created_at;
            $revision->updated_at = $this->updated_at;
            $revision->{$this->getIsCurrentColumn()} = false;
            $revision->{$this->getIsPublishedColumn()} = false;

            $revision->saveQuietly(['timestamps' => false]); // Preserve the existing updated_at

            $this->setPublisher();
            $this->pruneRevisions();

            $this->fireModelEvent('createdRevision');
        });

    }

}
