<?php

use Bakery\Tests\Stubs\Models\Upvote;
use Bakery\Tests\Stubs\Models\Article;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/* @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Upvote::class, function (Faker\Generator $faker) {
    return [
        'upvoteable_type' => Article::class,
        'upvoteable_id' => function () {
            return factory(Article::class)->create()->id;
        },
    ];
});
