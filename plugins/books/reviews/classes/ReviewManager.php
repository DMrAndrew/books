<?php

namespace Books\Reviews\Classes;

use Validator;
use ValidationException;
use RainLab\User\Facades\Auth;
use Books\Reviews\Models\Review;
use Mtvs\Reviews\IndexesReviews;
use Books\Reviews\Behaviors\Reviewable;

class ReviewManager
{

    use IndexesReviews;

    public function store($data)
    {
        if (!in_array($data['reviewable_type'], config('reviews.reviewables'))) {
            throw new ValidationException(['reviewable_type' => "The reviewable_type does not exist."]);
        }

        if (!$reviewable = $data['reviewable_type']::find($data['reviewable_id'])) {
            throw new ValidationException(['reviewable_id' => "The reviewable model was not found."]);
        }

        $validator = $this->validator($data);
        $user = $data['user'] ?? Auth::getUser();

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }


        // Reject multiple reviews from a single user
        if ($user->hasAlreadyReviewed($reviewable)) {
            throw new ValidationException(['reviewable_id' => 'It has already been reviewed by the user.']);
        }

        $review = $user->reviews()
            ->make($data)
            ->reviewable()
            ->associate($reviewable);

        $review->save();
        $review->approve();

        return $review;
    }

    public function update(Reviewable $key, array $data)
    {
        $review = $this->findReviewOrFail($key);

        $validator = $this->validator($data);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $review->update($validator->validated());

        return $review;
    }

    public function destroy(Reviewable $key)
    {
        $review = $this->findReviewOrFail($key);

        return $review->delete();
    }

    protected function findReviewOrFail(Reviewable $key)
    {
        return Auth::getUser()
            ->reviews()
            ->anyApprovalStatus()
            ->findOrFail($key);
    }


    protected function validator(array $data): \Illuminate\Contracts\Validation\Validator|\Illuminate\Validation\Validator
    {
        return Validator::make(
            $data,
            $this->getValidatorRules(),
            $this->getCustomMessages(),
            []
        );
    }

    protected function getValidatorRules(): array
    {
        return (new Review)->rules;
    }

    protected function getCustomMessages(): array
    {
        return (array)(new Review())->customMessages;
    }


}
