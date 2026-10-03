<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Project> */
class ProjectFactory extends Factory
{
    /** @return array{name: string} */
    public function definition(): array
    {
        return ['name' => fake()->unique()->sentence(3)];
    }
}
