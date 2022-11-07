<?php

use Books\Reviews\Models\Review;

return [
	// The reviews model
	'model' => Review::class,

	// The models that can be reviewed
	'reviewables' => [
//		RainLab\Blog\Models\Post::class
	],

	// Also change the value in the UI: in the ReviewsStars component
	'rating_max' => 5,
];
