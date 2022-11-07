<?php

namespace Books\Reviews\Behaviors;

use ValidationException;
use Books\Reviews\Models\Review;
use Books\Reviews\Classes\ReviewManager;
use October\Rain\Extension\ExtensionBase;

class Reviewable extends ExtensionBase
{
    use \Mtvs\Reviews\Reviewable;

    public function __construct(protected $model)
    {
        $this->model->morphMany['reviews'] = [Review::class, 'name' => 'reviewable'];
    }

    public function reviews()
    {
        return $this->model->reviews();
    }

    /**
     * @param array $data
     * @param ReviewManager $manager
     * @return Reviewable
     * @throws ValidationException
     */
    public function updateReview(array $data, ReviewManager $manager): Reviewable
    {
        return $manager->update($this, $data);
    }

    /**
     * @param ReviewManager $manager
     * @return mixed
     */
    public function destroyReview(ReviewManager $manager): mixed
    {
        return $manager->destroy($this);
    }
}
