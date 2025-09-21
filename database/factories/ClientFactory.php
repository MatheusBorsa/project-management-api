<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'name'         => $this->faker->company,
            'contact_name' => $this->faker->name,
            'email'        => $this->faker->unique()->companyEmail,
            'phone'        => $this->faker->phoneNumber,
            'notes'        => $this->faker->sentence(10),
        ];
    }
}
