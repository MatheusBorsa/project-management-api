<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'client_id'    => Client::factory(),
            'title'        => $this->faker->sentence(6, true),
            'description'  => $this->faker->paragraph(3, true),
            'deadline'     => $this->faker->dateTimeBetween('now', '+1 month'),
            'status'       => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'on_hold']),
            'assigned_to'  => User::factory(),
            'image_path'   => $this->faker->imageUrl(640, 480, 'business'),
        ];
    }
}
