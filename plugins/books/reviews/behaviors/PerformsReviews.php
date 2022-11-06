<?php

namespace Books\Reviews\Behaviors;

use ValidationException;
use RainLab\User\Models\User;
use Books\Reviews\Models\Review;
use Books\Reviews\Classes\ReviewManager;
use October\Rain\Extension\ExtensionBase;
use October\Rain\Database\Relations\HasMany;

class PerformsReviews extends ExtensionBase
{
    use \Mtvs\Reviews\PerformsReviews;

    public function __construct(protected User $model)
    {
        $this->model->hasMany['reviews'] = [Review::class];
    }

    /**
     * @return HasMany
     */
    public function reviews(): HasMany
    {
        return $this->model->reviews();
    }

    /**
     * @param array $data
     * @param ReviewManager $manager
     * @return Reviewable
     * @throws ValidationException
     */
    public function storeReview(array $data, ReviewManager $manager): Reviewable
    {
        return $manager->store(array_merge($data, ['user' => $this->model]));
    }

    /**
     * @param Reviewable $reviewable
     * @param ReviewManager $manager
     * @return mixed
     */
    public function destroyReview(Reviewable $reviewable, ReviewManager $manager): mixed
    {
        return $manager->destroy($reviewable);
    }

}
