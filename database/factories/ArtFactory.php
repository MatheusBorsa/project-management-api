<?php

namespace Database\Factories;

use App\Models\Art;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtFactory extends Factory
{
    protected $model = Art::class;

    public function definition()
    {
        return [
            'task_id' => Task::factory(),
            'title' => $this->faker->words(3, true),
            'art_path' => 'arts/' . $this->faker->uuid . '.jpg',
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'approved',
            ];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
            ];
        });
    }

    public function forTask(Task $task)
    {
        return $this->state(function (array $attributes) use ($task) {
            return [
                'task_id' => $task->id,
            ];
        });
    }

    public function withTitle(string $title)
    {
        return $this->state(function (array $attributes) use ($title) {
            return [
                'title' => $title,
            ];
        });
    }
}